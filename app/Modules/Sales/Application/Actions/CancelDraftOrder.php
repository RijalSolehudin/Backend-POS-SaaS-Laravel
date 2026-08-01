<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Actions;

use App\Modules\Sales\Application\Exceptions\OrderException;
use App\Modules\Sales\Domain\Enums\OrderStatus;
use App\Modules\Sales\Domain\Models\IdempotencyRecord;
use App\Modules\Sales\Domain\Models\Order;
use App\Modules\Tenancy\Application\Data\PosOutletContext;
use Illuminate\Support\Facades\DB;

final readonly class CancelDraftOrder
{
    public function handle(PosOutletContext $context, string $orderId, string $reason, string $idempotencyKey): Order
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw OrderException::reasonRequired();
        }

        if (trim($idempotencyKey) === '') {
            throw OrderException::idempotencyKeyRequired();
        }

        $requestHash = hash('sha256', json_encode([
            'order_id' => $orderId,
            'reason' => $reason,
        ], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($context, $orderId, $reason, $idempotencyKey, $requestHash): Order {
            $record = IdempotencyRecord::query()
                ->where('tenant_id', $context->tenantId)
                ->where('outlet_id', $context->outletId)
                ->where('user_id', $context->userId)
                ->where('action', 'orders.cancel')
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($record instanceof IdempotencyRecord) {
                if ($record->request_hash !== $requestHash || $record->resource_id === null) {
                    throw OrderException::idempotencyConflict();
                }

                $order = $this->scopedOrder($context, $record->resource_id);

                if (! $order instanceof Order) {
                    throw OrderException::idempotencyConflict();
                }

                return $order;
            }

            $order = $this->scopedOrder($context, $orderId);

            if (! $order instanceof Order) {
                throw OrderException::notFound();
            }

            if ($order->status !== OrderStatus::Draft) {
                throw OrderException::notDraft();
            }

            $order->forceFill([
                'status' => OrderStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_by' => $context->userId,
                'cancel_reason' => $reason,
            ])->save();

            IdempotencyRecord::query()->create([
                'tenant_id' => $context->tenantId,
                'outlet_id' => $context->outletId,
                'user_id' => $context->userId,
                'action' => 'orders.cancel',
                'idempotency_key' => $idempotencyKey,
                'request_hash' => $requestHash,
                'resource_type' => 'sales_order',
                'resource_id' => $order->id,
                'response_status' => 200,
                'response_body' => ['order_id' => $order->id],
                'expires_at' => now()->addDay(),
            ]);

            return $order->refresh();
        });
    }

    private function scopedOrder(PosOutletContext $context, string $orderId): ?Order
    {
        return Order::query()
            ->with(['items', 'payments'])
            ->where('tenant_id', $context->tenantId)
            ->where('outlet_id', $context->outletId)
            ->where('user_id', $context->userId)
            ->whereKey($orderId)
            ->lockForUpdate()
            ->first();
    }
}
