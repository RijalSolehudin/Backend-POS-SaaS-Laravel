<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Actions;

use App\Modules\Sales\Application\Exceptions\ShiftException;
use App\Modules\Sales\Domain\Enums\ShiftStatus;
use App\Modules\Sales\Domain\Models\Shift;
use App\Modules\Tenancy\Application\Data\PosOutletContext;
use Illuminate\Support\Facades\DB;

final readonly class CloseShift
{
    public function __construct(
        private SummarizeShift $summaries,
        private RecordSalesAuditEvent $audit,
    ) {}

    public function handle(PosOutletContext $context, string $shiftId, int $closingCashMinor): Shift
    {
        return DB::transaction(function () use ($context, $shiftId, $closingCashMinor): Shift {
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

            return $shift;
        });
    }
}
