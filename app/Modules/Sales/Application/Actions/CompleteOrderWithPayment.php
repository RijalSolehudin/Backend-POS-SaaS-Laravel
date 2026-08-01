<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Actions;

use App\Modules\Sales\Application\Exceptions\OrderException;
use App\Modules\Sales\Domain\Enums\OrderStatus;
use App\Modules\Sales\Domain\Enums\PaymentMethod;
use App\Modules\Sales\Domain\Enums\PaymentStatus;
use App\Modules\Sales\Domain\Enums\ShiftStatus;
use App\Modules\Sales\Domain\Models\IdempotencyRecord;
use App\Modules\Sales\Domain\Models\Order;
use App\Modules\Sales\Domain\Models\OrderItem;
use App\Modules\Sales\Domain\Models\Payment;
use App\Modules\Sales\Domain\Models\Shift;
use App\Modules\Tenancy\Application\Data\PosOutletContext;
use Illuminate\Support\Facades\DB;

final readonly class CompleteOrderWithPayment
{
    public function handle(
        PosOutletContext $context,
        string $orderId,
        PaymentMethod $method,
        int $amountMinor,
        string $currency,
        string $idempotencyKey,
    ): Order {
        if (trim($idempotencyKey) === '') {
            throw OrderException::idempotencyKeyRequired();
        }

        $requestHash = hash('sha256', json_encode([
            'order_id' => $orderId,
            'method' => $method->value,
            'amount_minor' => $amountMinor,
            'currency' => $currency,
        ], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($context, $orderId, $method, $amountMinor, $currency, $idempotencyKey, $requestHash): Order {
            $record = IdempotencyRecord::query()
                ->where('tenant_id', $context->tenantId)
                ->where('outlet_id', $context->outletId)
                ->where('user_id', $context->userId)
                ->where('action', 'orders.complete')
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

            $shift = $this->openShift($context, $order->shift_id);

            if (OrderItem::query()->where('tenant_id', $context->tenantId)->where('order_id', $order->id)->count() === 0) {
                throw OrderException::itemsRequired();
            }

            if ($amountMinor !== $order->total_minor) {
                throw OrderException::paymentAmountMismatch();
            }

            if ($currency !== $order->currency) {
                throw OrderException::paymentCurrencyMismatch();
            }

            Payment::query()->create([
                'tenant_id' => $context->tenantId,
                'outlet_id' => $context->outletId,
                'shift_id' => $shift->id,
                'order_id' => $order->id,
                'method' => $method,
                'status' => PaymentStatus::Recorded,
                'amount_minor' => $amountMinor,
                'currency' => $currency,
                'recorded_at' => now(),
            ]);

            $order->forceFill([
                'status' => OrderStatus::Completed,
                'completed_at' => now(),
            ])->save();

            $shift->forceFill([
                'gross_sales_minor' => $shift->gross_sales_minor + $order->total_minor,
                'expected_cash_minor' => $shift->expected_cash_minor + ($method === PaymentMethod::Cash ? $amountMinor : 0),
            ])->save();

            IdempotencyRecord::query()->create([
                'tenant_id' => $context->tenantId,
                'outlet_id' => $context->outletId,
                'user_id' => $context->userId,
                'action' => 'orders.complete',
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

    private function openShift(PosOutletContext $context, string $shiftId): Shift
    {
        $shift = Shift::query()
            ->where('tenant_id', $context->tenantId)
            ->where('outlet_id', $context->outletId)
            ->where('user_id', $context->userId)
            ->whereKey($shiftId)
            ->where('status', ShiftStatus::Open)
            ->lockForUpdate()
            ->first();

        if (! $shift instanceof Shift) {
            throw OrderException::activeShiftRequired();
        }

        return $shift;
    }
}
