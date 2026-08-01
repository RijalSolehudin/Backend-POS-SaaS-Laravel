<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Actions;

use App\Modules\Sales\Application\Exceptions\OrderException;
use App\Modules\Sales\Application\Services\IdempotencyStore;
use App\Modules\Sales\Domain\Enums\OrderStatus;
use App\Modules\Sales\Domain\Enums\PaymentStatus;
use App\Modules\Sales\Domain\Models\IdempotencyRecord;
use App\Modules\Sales\Domain\Models\Order;
use App\Modules\Sales\Domain\Models\Payment;
use App\Modules\Sales\Domain\Models\Shift;
use Illuminate\Support\Facades\DB;

final readonly class VoidCompletedOrder
{
    public function __construct(
        private SummarizeShift $summaries,
        private ConsumeSensitiveActionApproval $approvals,
        private RecordSalesAuditEvent $audit,
        private IdempotencyStore $idempotency,
    ) {}

    public static function approvalFingerprint(string $orderId, string $reason): string
    {
        return hash('sha256', json_encode([
            'order_id' => $orderId,
            'reason' => trim($reason),
        ], JSON_THROW_ON_ERROR));
    }

    public function handle(
        string $tenantId,
        string $orderId,
        string $actorUserId,
        string $reason,
        string $idempotencyKey,
        ?string $approvalId,
    ): Order {
        $reason = trim($reason);

        if ($reason === '') {
            throw OrderException::reasonRequired();
        }

        if (trim($idempotencyKey) === '') {
            throw OrderException::idempotencyKeyRequired();
        }

        $requestHash = self::approvalFingerprint($orderId, $reason);

        return DB::transaction(function () use ($tenantId, $orderId, $actorUserId, $reason, $idempotencyKey, $approvalId, $requestHash): Order {
            $order = Order::query()
                ->with(['items', 'payments'])
                ->where('tenant_id', $tenantId)
                ->whereKey($orderId)
                ->lockForUpdate()
                ->first();

            if (! $order instanceof Order) {
                throw OrderException::notFound();
            }

            $record = $this->idempotency->findForUpdate(
                tenantId: $tenantId,
                outletId: $order->outlet_id,
                userId: $actorUserId,
                action: 'orders.void',
                idempotencyKey: $idempotencyKey,
            );

            if ($record instanceof IdempotencyRecord) {
                if ($record->request_hash !== $requestHash || $record->resource_id === null) {
                    throw OrderException::idempotencyConflict();
                }

                return $order;
            }

            if ($order->status !== OrderStatus::Completed) {
                throw OrderException::notCompleted();
            }

            $this->approvals->handle(
                tenantId: $tenantId,
                outletId: $order->outlet_id,
                performerUserId: $actorUserId,
                approvalId: $approvalId,
                action: 'orders.void',
                targetType: 'sales_order',
                targetId: $order->id,
                requestFingerprint: $requestHash,
            );

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

            $this->idempotency->create(
                tenantId: $tenantId,
                outletId: $order->outlet_id,
                userId: $actorUserId,
                action: 'orders.void',
                idempotencyKey: $idempotencyKey,
                requestHash: $requestHash,
                resourceType: 'sales_order',
                resourceId: $order->id,
                responseStatus: 200,
                responseBody: ['order_id' => $order->id],
            );

            $this->audit->handle(
                tenantId: $tenantId,
                outletId: $order->outlet_id,
                actorUserId: $actorUserId,
                eventType: 'order.voided',
                targetType: 'sales_order',
                targetId: $order->id,
                outcome: 'voided',
                reason: $reason,
                correlationId: $idempotencyKey,
                metadata: [
                    'approval_id' => $approvalId,
                    'shift_id' => $order->shift_id,
                    'total_minor' => $order->total_minor,
                    'currency' => $order->currency,
                ],
            );

            return $order->refresh();
        });
    }
}
