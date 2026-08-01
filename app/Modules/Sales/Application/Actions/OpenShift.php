<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Actions;

use App\Modules\Sales\Application\Exceptions\ShiftException;
use App\Modules\Sales\Domain\Enums\ShiftStatus;
use App\Modules\Sales\Domain\Models\Shift;
use App\Modules\Tenancy\Application\Contracts\TenantCatalogReference;
use App\Modules\Tenancy\Application\Data\PosOutletContext;
use Illuminate\Support\Facades\DB;

final readonly class OpenShift
{
    public function __construct(private TenantCatalogReference $tenancy) {}

    public function handle(PosOutletContext $context, int $openingCashMinor): Shift
    {
        $tenant = $this->tenancy->tenant($context->tenantId);

        if ($tenant === null) {
            throw ShiftException::notFound();
        }

        return DB::transaction(function () use ($context, $openingCashMinor, $tenant): Shift {
            $openShiftKey = $this->openShiftKey($context);

            $exists = Shift::query()
                ->where('open_shift_key', $openShiftKey)
                ->lockForUpdate()
                ->exists();

            if ($exists) {
                throw ShiftException::alreadyOpen();
            }

            return Shift::query()->create([
                'tenant_id' => $context->tenantId,
                'outlet_id' => $context->outletId,
                'user_id' => $context->userId,
                'open_shift_key' => $openShiftKey,
                'status' => ShiftStatus::Open,
                'opened_at' => now(),
                'opening_cash_minor' => $openingCashMinor,
                'expected_cash_minor' => $openingCashMinor,
                'gross_sales_minor' => 0,
                'currency' => $tenant->currency,
            ]);
        });
    }

    private function openShiftKey(PosOutletContext $context): string
    {
        return $context->tenantId.':'.$context->outletId.':'.$context->userId;
    }
}
