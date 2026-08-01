<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Actions;

use App\Modules\Sales\Application\Exceptions\OrderException;
use App\Modules\Sales\Application\Services\IdempotencyStore;
use App\Modules\Sales\Domain\Enums\OrderStatus;
use App\Modules\Sales\Domain\Enums\ShiftStatus;
use App\Modules\Sales\Domain\Models\IdempotencyRecord;
use App\Modules\Sales\Domain\Models\Order;
use App\Modules\Sales\Domain\Models\OrderNumberCounter;
use App\Modules\Sales\Domain\Models\Shift;
use App\Modules\Tenancy\Application\Contracts\TenantCatalogReference;
use App\Modules\Tenancy\Application\Data\PosOutletContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class CreateDraftOrder
{
    public function __construct(
        private TenantCatalogReference $tenancy,
        private IdempotencyStore $idempotency,
    ) {}

    public function handle(PosOutletContext $context, string $idempotencyKey): Order
    {
        if (trim($idempotencyKey) === '') {
            throw OrderException::idempotencyKeyRequired();
        }

        $requestHash = hash('sha256', 'create_draft_order');

        return DB::transaction(function () use ($context, $idempotencyKey, $requestHash): Order {
            $record = $this->idempotency->findForContext($context, 'orders.create', $idempotencyKey);

            if ($record instanceof IdempotencyRecord) {
                if ($record->request_hash !== $requestHash || $record->resource_id === null) {
                    throw OrderException::idempotencyConflict();
                }

                $order = $this->draftOrder($context, $record->resource_id);

                if (! $order instanceof Order) {
                    throw OrderException::idempotencyConflict();
                }

                return $order;
            }

            $shift = $this->openShift($context);
            $tenant = $this->tenancy->tenant($context->tenantId);
            $outlet = $this->tenancy->activeOutlet($context->tenantId, $context->outletId);

            if ($tenant === null || $outlet === null) {
                throw OrderException::activeShiftRequired();
            }

            $order = Order::query()->create([
                'tenant_id' => $context->tenantId,
                'outlet_id' => $context->outletId,
                'shift_id' => $shift->id,
                'user_id' => $context->userId,
                'order_number' => $this->nextOrderNumber($context, $outlet->code, $tenant->timezone),
                'status' => OrderStatus::Draft,
                'subtotal_minor' => 0,
                'discount_minor' => 0,
                'service_charge_minor' => 0,
                'tax_minor' => 0,
                'total_minor' => 0,
                'currency' => $tenant->currency,
            ]);

            $this->idempotency->createForContext(
                context: $context,
                action: 'orders.create',
                idempotencyKey: $idempotencyKey,
                requestHash: $requestHash,
                resourceType: 'sales_order',
                resourceId: $order->id,
                responseStatus: 201,
                responseBody: ['order_id' => $order->id],
            );

            return $order;
        });
    }

    private function openShift(PosOutletContext $context): Shift
    {
        $shift = Shift::query()
            ->where('tenant_id', $context->tenantId)
            ->where('outlet_id', $context->outletId)
            ->where('user_id', $context->userId)
            ->where('status', ShiftStatus::Open)
            ->lockForUpdate()
            ->first();

        if (! $shift instanceof Shift) {
            throw OrderException::activeShiftRequired();
        }

        return $shift;
    }

    private function draftOrder(PosOutletContext $context, string $orderId): ?Order
    {
        return Order::query()
            ->where('tenant_id', $context->tenantId)
            ->where('outlet_id', $context->outletId)
            ->where('user_id', $context->userId)
            ->whereKey($orderId)
            ->first();
    }

    private function nextOrderNumber(PosOutletContext $context, string $outletCode, string $timezone): string
    {
        $businessDate = CarbonImmutable::now($timezone)->toDateString();
        $counter = OrderNumberCounter::query()
            ->where('tenant_id', $context->tenantId)
            ->where('outlet_id', $context->outletId)
            ->where('business_date', $businessDate)
            ->lockForUpdate()
            ->first();

        if (! $counter instanceof OrderNumberCounter) {
            $counter = OrderNumberCounter::query()->create([
                'tenant_id' => $context->tenantId,
                'outlet_id' => $context->outletId,
                'business_date' => $businessDate,
                'next_sequence' => 1,
            ]);
        }

        $sequence = $counter->next_sequence;
        $counter->forceFill(['next_sequence' => $sequence + 1])->save();

        return sprintf('%s-%s-%04d', $outletCode, str_replace('-', '', $businessDate), $sequence);
    }
}
