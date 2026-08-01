<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Actions;

use App\Modules\Sales\Application\Exceptions\OrderException;
use App\Modules\Sales\Application\Exceptions\RefundException;
use App\Modules\Sales\Domain\Enums\OrderStatus;
use App\Modules\Sales\Domain\Enums\PaymentStatus;
use App\Modules\Sales\Domain\Enums\RefundStatus;
use App\Modules\Sales\Domain\Models\IdempotencyRecord;
use App\Modules\Sales\Domain\Models\Order;
use App\Modules\Sales\Domain\Models\Payment;
use App\Modules\Sales\Domain\Models\Refund;
use App\Modules\Tenancy\Application\Data\PosOutletContext;
use Illuminate\Support\Facades\DB;

final readonly class RecordFullRefund
{
    public function __construct(
        private ConsumeSensitiveActionApproval $approvals,
        private RecordSalesAuditEvent $audit,
    ) {}

    public static function approvalFingerprint(string $orderId, int $amountMinor, string $currency, string $reason): string
    {
        return hash('sha256', json_encode([
            'order_id' => $orderId,
            'amount_minor' => $amountMinor,
            'currency' => strtoupper($currency),
            'reason' => trim($reason),
        ], JSON_THROW_ON_ERROR));
    }

    public function handle(
        PosOutletContext $context,
        string $orderId,
        int $amountMinor,
        string $currency,
        string $reason,
        string $idempotencyKey,
        ?string $approvalId,
    ): Refund {
        $currency = strtoupper($currency);
        $reason = trim($reason);

        if ($reason === '') {
            throw RefundException::reasonRequired();
        }

        if (trim($idempotencyKey) === '') {
            throw RefundException::idempotencyKeyRequired();
        }

        $requestHash = self::approvalFingerprint($orderId, $amountMinor, $currency, $reason);

        return DB::transaction(function () use ($context, $orderId, $amountMinor, $currency, $reason, $idempotencyKey, $approvalId, $requestHash): Refund {
            $record = IdempotencyRecord::query()
                ->where('tenant_id', $context->tenantId)
                ->where('outlet_id', $context->outletId)
                ->where('user_id', $context->userId)
                ->where('action', 'payments.refund')
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($record instanceof IdempotencyRecord) {
                if ($record->request_hash !== $requestHash || $record->resource_id === null) {
                    throw RefundException::idempotencyConflict();
                }

                $refund = Refund::query()
                    ->where('tenant_id', $context->tenantId)
                    ->whereKey($record->resource_id)
                    ->first();

                if (! $refund instanceof Refund) {
                    throw RefundException::orderNotRefundable();
                }

                return $refund;
            }

            $order = Order::query()
                ->where('tenant_id', $context->tenantId)
                ->where('outlet_id', $context->outletId)
                ->whereKey($orderId)
                ->lockForUpdate()
                ->first();

            if (! $order instanceof Order) {
                throw OrderException::notFound();
            }

            if ($order->status !== OrderStatus::Completed) {
                throw RefundException::orderNotRefundable();
            }

            $payment = Payment::query()
                ->where('tenant_id', $context->tenantId)
                ->where('outlet_id', $context->outletId)
                ->where('order_id', $order->id)
                ->where('status', PaymentStatus::Recorded)
                ->lockForUpdate()
                ->first();

            if (! $payment instanceof Payment) {
                throw RefundException::orderNotRefundable();
            }

            if (Refund::query()->where('tenant_id', $context->tenantId)->where('payment_id', $payment->id)->lockForUpdate()->exists()) {
                throw RefundException::alreadyRefunded();
            }

            if ($amountMinor !== $payment->amount_minor) {
                throw RefundException::amountMismatch();
            }

            if ($currency !== $payment->currency) {
                throw RefundException::currencyMismatch();
            }

            $this->approvals->handle(
                tenantId: $context->tenantId,
                outletId: $context->outletId,
                performerUserId: $context->userId,
                approvalId: $approvalId,
                action: 'payments.refund',
                targetType: 'sales_order',
                targetId: $order->id,
                requestFingerprint: $requestHash,
            );

            $refund = Refund::query()->create([
                'tenant_id' => $context->tenantId,
                'outlet_id' => $context->outletId,
                'shift_id' => $order->shift_id,
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'approval_id' => $approvalId,
                'refunded_by' => $context->userId,
                'method' => $payment->method,
                'status' => RefundStatus::Recorded,
                'amount_minor' => $amountMinor,
                'currency' => $currency,
                'reason' => $reason,
                'recorded_at' => now(),
            ]);

            IdempotencyRecord::query()->create([
                'tenant_id' => $context->tenantId,
                'outlet_id' => $context->outletId,
                'user_id' => $context->userId,
                'action' => 'payments.refund',
                'idempotency_key' => $idempotencyKey,
                'request_hash' => $requestHash,
                'resource_type' => 'sales_refund',
                'resource_id' => $refund->id,
                'response_status' => 201,
                'response_body' => ['refund_id' => $refund->id],
                'expires_at' => now()->addDay(),
            ]);

            $this->audit->handle(
                tenantId: $context->tenantId,
                outletId: $context->outletId,
                actorUserId: $context->userId,
                eventType: 'payment.refunded',
                targetType: 'sales_refund',
                targetId: $refund->id,
                outcome: 'recorded',
                reason: $reason,
                correlationId: $idempotencyKey,
                metadata: [
                    'approval_id' => $approvalId,
                    'order_id' => $order->id,
                    'payment_id' => $payment->id,
                    'amount_minor' => $amountMinor,
                    'currency' => $currency,
                ],
            );

            return $refund;
        });
    }
}
