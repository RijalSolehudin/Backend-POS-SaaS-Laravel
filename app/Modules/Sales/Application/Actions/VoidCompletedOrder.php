<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Actions;

use App\Modules\Sales\Application\Exceptions\OrderException;
use App\Modules\Sales\Domain\Enums\OrderStatus;
use App\Modules\Sales\Domain\Enums\PaymentStatus;
use App\Modules\Sales\Domain\Models\IdempotencyRecord;
use App\Modules\Sales\Domain\Models\Order;
use App\Modules\Sales\Domain\Models\Payment;
use App\Modules\Sales\Domain\Models\Shift;
use Illuminate\Support\Facades\DB;

final readonly class VoidCompletedOrder
{
    public function __construct(private SummarizeShift $summaries) {}

    public function handle(string $tenantId, string $orderId, string $actorUserId, string $reason, string $idempotencyKey): Order
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

        return DB::transaction(function () use ($tenantId, $orderId, $actorUserId, $reason, $idempotencyKey, $requestHash): Order {
            $order = Order::query()
                ->with(['items', 'payments'])
                ->where('tenant_id', $tenantId)
                ->whereKey($orderId)
                ->lockForUpdate()
                ->first();

            if (! $order instanceof Order) {
                throw OrderException::notFound();
            }

            $record = IdempotencyRecord::query()
                ->where('tenant_id', $tenantId)
                ->where('outlet_id', $order->outlet_id)
                ->where('user_id', $actorUserId)
                ->where('action', 'orders.void')
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($record instanceof IdempotencyRecord) {
                if ($record->request_hash !== $requestHash || $record->resource_id === null) {
                    throw OrderException::idempotencyConflict();
                }

                return $order;
            }

            if ($order->status !== OrderStatus::Completed) {
                throw OrderException::notCompleted();
            }

            Payment::query()
                ->where('tenant_id', $tenantId)
                ->where('order_id', $order->id)
                ->where('status', PaymentStatus::Recorded)
                ->lockForUpdate()
                ->get()
                ->each(function (Payment $payment) use ($actorUserId, $reason): void {
                    $payment->forceFill([
                        'status' => PaymentStatus::Voided,
                        'voided_at' => now(),
                        'voided_by' => $actorUserId,
                        'void_reason' => $reason,
                    ])->save();
                });

            $order->forceFill([
                'status' => OrderStatus::Voided,
                'voided_at' => now(),
                'voided_by' => $actorUserId,
                'void_reason' => $reason,
            ])->save();

            $shift = Shift::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($order->shift_id)
                ->lockForUpdate()
                ->first();

            if ($shift instanceof Shift) {
                $summary = $this->summaries->fromShift($shift);
                $shift->forceFill([
                    'expected_cash_minor' => $summary->expectedCashMinor,
                    'gross_sales_minor' => $summary->grossSalesMinor,
                ])->save();
            }

            IdempotencyRecord::query()->create([
                'tenant_id' => $tenantId,
                'outlet_id' => $order->outlet_id,
                'user_id' => $actorUserId,
                'action' => 'orders.void',
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
}
