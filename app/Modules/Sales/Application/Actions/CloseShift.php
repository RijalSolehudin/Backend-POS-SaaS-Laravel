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

            $shift->forceFill([
                'status' => ShiftStatus::Closed,
                'open_shift_key' => null,
                'closing_cash_minor' => $closingCashMinor,
                'closed_at' => now(),
            ])->save();

            return $shift;
        });
    }
}
