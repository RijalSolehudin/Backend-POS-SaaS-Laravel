<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Actions;

use App\Modules\Sales\Application\Exceptions\ShiftException;
use App\Modules\Sales\Application\Services\IdempotencyStore;
use App\Modules\Sales\Domain\Enums\ShiftStatus;
use App\Modules\Sales\Domain\Models\IdempotencyRecord;
use App\Modules\Sales\Domain\Models\Shift;
use App\Modules\Tenancy\Application\Data\PosOutletContext;
use Illuminate\Support\Facades\DB;

final readonly class CloseShift
{
    public function __construct(
        private SummarizeShift $summaries,
        private RecordSalesAuditEvent $audit,
        private IdempotencyStore $idempotency,
    ) {}

    public function handle(PosOutletContext $context, string $shiftId, int $closingCashMinor, string $idempotencyKey): Shift
    {
        if (trim($idempotencyKey) === '') {
            throw ShiftException::idempotencyKeyRequired();
        }

        $requestHash = hash('sha256', json_encode([
            'shift_id' => $shiftId,
            'closing_cash_minor' => $closingCashMinor,
        ], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($context, $shiftId, $closingCashMinor, $idempotencyKey, $requestHash): Shift {
            $record = $this->idempotency->findForContext($context, 'shifts.close', $idempotencyKey);

            if ($record instanceof IdempotencyRecord) {
                if ($record->request_hash !== $requestHash || $record->resource_id === null) {
                    throw ShiftException::idempotencyConflict();
                }

                $shift = $this->scopedShift($context, $record->resource_id);

                if (! $shift instanceof Shift) {
                    throw ShiftException::idempotencyConflict();
                }

                return $shift;
            }

            $shift = $this->scopedShift($context, $shiftId);

            if (! $shift instanceof Shift) {
                throw ShiftException::notFound();
            }

            if ($shift->status !== ShiftStatus::Open) {
                throw ShiftException::notOpen();
            }

            $summary = $this->summaries->fromShift($shift);
            $cashVarianceMinor = $closingCashMinor - $summary->expectedCashMinor;

            $shift->forceFill([
                'status' => ShiftStatus::Closed,
                'open_shift_key' => null,
                'closing_cash_minor' => $closingCashMinor,
                'expected_cash_minor' => $summary->expectedCashMinor,
                'gross_sales_minor' => $summary->grossSalesMinor,
                'closed_at' => now(),
            ])->save();

            if ($cashVarianceMinor !== 0) {
                $this->audit->handle(
                    tenantId: $context->tenantId,
                    outletId: $context->outletId,
                    actorUserId: $context->userId,
                    eventType: 'shift.discrepancy.recorded',
                    targetType: 'sales_shift',
                    targetId: $shift->id,
                    outcome: 'recorded',
                    correlationId: $shift->id,
                    metadata: [
                        'expected_cash_minor' => $summary->expectedCashMinor,
                        'closing_cash_minor' => $closingCashMinor,
                        'cash_variance_minor' => $cashVarianceMinor,
                    ],
                );
            }

            $this->idempotency->createForContext(
                context: $context,
                action: 'shifts.close',
                idempotencyKey: $idempotencyKey,
                requestHash: $requestHash,
                resourceType: 'sales_shift',
                resourceId: $shift->id,
                responseStatus: 200,
                responseBody: ['shift_id' => $shift->id],
            );

            return $shift;
        });
    }

    private function scopedShift(PosOutletContext $context, string $shiftId): ?Shift
    {
        return Shift::query()
            ->where('tenant_id', $context->tenantId)
            ->where('outlet_id', $context->outletId)
            ->where('user_id', $context->userId)
            ->whereKey($shiftId)
            ->lockForUpdate()
            ->first();
    }
}
