<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Actions;

use App\Modules\Sales\Application\Exceptions\CashMovementException;
use App\Modules\Sales\Application\Exceptions\ShiftException;
use App\Modules\Sales\Application\Services\IdempotencyStore;
use App\Modules\Sales\Domain\Enums\CashMovementType;
use App\Modules\Sales\Domain\Enums\ShiftStatus;
use App\Modules\Sales\Domain\Models\CashMovement;
use App\Modules\Sales\Domain\Models\IdempotencyRecord;
use App\Modules\Sales\Domain\Models\Shift;
use App\Modules\Tenancy\Application\Data\PosOutletContext;
use Illuminate\Support\Facades\DB;

final readonly class RecordCashMovement
{
    public function __construct(
        private ConsumeSensitiveActionApproval $approvals,
        private RecordSalesAuditEvent $audit,
        private IdempotencyStore $idempotency,
    ) {}

    public static function approvalFingerprint(string $shiftId, CashMovementType $type, int $amountMinor, string $currency, string $reason): string
    {
        return hash('sha256', json_encode([
            'shift_id' => $shiftId,
            'type' => $type->value,
            'amount_minor' => $amountMinor,
            'currency' => strtoupper($currency),
            'reason' => trim($reason),
        ], JSON_THROW_ON_ERROR));
    }

    public function handle(
        PosOutletContext $context,
        string $shiftId,
        CashMovementType $type,
        int $amountMinor,
        string $currency,
        string $reason,
        string $idempotencyKey,
        ?string $approvalId,
    ): CashMovement {
        $currency = strtoupper($currency);
        $reason = trim($reason);

        if ($reason === '') {
            throw CashMovementException::reasonRequired();
        }

        if (trim($idempotencyKey) === '') {
            throw CashMovementException::idempotencyKeyRequired();
        }

        $requestHash = self::approvalFingerprint($shiftId, $type, $amountMinor, $currency, $reason);

        return DB::transaction(function () use ($context, $shiftId, $type, $amountMinor, $currency, $reason, $idempotencyKey, $approvalId, $requestHash): CashMovement {
            $record = $this->idempotency->findForContext($context, 'cash_movements.record', $idempotencyKey);

            if ($record instanceof IdempotencyRecord) {
                if ($record->request_hash !== $requestHash || $record->resource_id === null) {
                    throw CashMovementException::idempotencyConflict();
                }

                $movement = CashMovement::query()
                    ->where('tenant_id', $context->tenantId)
                    ->whereKey($record->resource_id)
                    ->first();

                if (! $movement instanceof CashMovement) {
                    throw ShiftException::notFound();
                }

                return $movement;
            }

            $shift = Shift::query()
                ->where('tenant_id', $context->tenantId)
                ->where('outlet_id', $context->outletId)
                ->where('user_id', $context->userId)
                ->whereKey($shiftId)
                ->lockForUpdate()
                ->first();

            if (! $shift instanceof Shift) {
                throw ShiftException::notFound();
            }

            if ($shift->status !== ShiftStatus::Open) {
                throw CashMovementException::shiftNotOpen();
            }

            if ($currency !== $shift->currency) {
                throw ShiftException::currencyMismatch();
            }

            if ($type === CashMovementType::CashOut && $amountMinor > $this->cashOutApprovalThresholdMinor()) {
                $this->approvals->handle(
                    tenantId: $context->tenantId,
                    outletId: $context->outletId,
                    performerUserId: $context->userId,
                    approvalId: $approvalId,
                    action: 'cash_movements.cash_out',
                    targetType: 'sales_shift',
                    targetId: $shift->id,
                    requestFingerprint: $requestHash,
                );
            }

            $movement = CashMovement::query()->create([
                'tenant_id' => $context->tenantId,
                'outlet_id' => $context->outletId,
                'shift_id' => $shift->id,
                'user_id' => $context->userId,
                'approval_id' => $approvalId,
                'type' => $type,
                'amount_minor' => $amountMinor,
                'currency' => $currency,
                'reason' => $reason,
                'recorded_at' => now(),
            ]);

            $this->idempotency->createForContext(
                context: $context,
                action: 'cash_movements.record',
                idempotencyKey: $idempotencyKey,
                requestHash: $requestHash,
                resourceType: 'sales_cash_movement',
                resourceId: $movement->id,
                responseStatus: 201,
                responseBody: ['cash_movement_id' => $movement->id],
            );

            $this->audit->handle(
                tenantId: $context->tenantId,
                outletId: $context->outletId,
                actorUserId: $context->userId,
                eventType: 'cash_movement.recorded',
                targetType: 'sales_cash_movement',
                targetId: $movement->id,
                outcome: 'recorded',
                reason: $reason,
                correlationId: $idempotencyKey,
                metadata: [
                    'approval_id' => $approvalId,
                    'shift_id' => $shift->id,
                    'type' => $type->value,
                    'amount_minor' => $amountMinor,
                    'currency' => $currency,
                ],
            );

            return $movement;
        });
    }

    private function cashOutApprovalThresholdMinor(): int
    {
        $threshold = config('sales.cash_out_approval_threshold_minor', 0);

        return is_int($threshold) ? $threshold : 0;
    }
}
