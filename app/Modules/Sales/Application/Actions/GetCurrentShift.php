<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Actions;

use App\Modules\Sales\Domain\Models\Shift;
use App\Modules\Tenancy\Application\Data\PosOutletContext;

final readonly class GetCurrentShift
{
    public function handle(PosOutletContext $context): ?Shift
    {
        return Shift::query()
            ->where('tenant_id', $context->tenantId)
            ->where('outlet_id', $context->outletId)
            ->where('user_id', $context->userId)
            ->where('open_shift_key', $this->openShiftKey($context))
            ->first();
    }

    private function openShiftKey(PosOutletContext $context): string
    {
        return $context->tenantId.':'.$context->outletId.':'.$context->userId;
    }
}
