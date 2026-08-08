<?php

declare(strict_types=1);

namespace App\Modules\Kitchen\Application\Actions;

use App\Modules\Kitchen\Application\Data\KitchenStationInput;
use App\Modules\Kitchen\Application\Exceptions\KitchenException;
use App\Modules\Kitchen\Domain\Enums\KitchenStatus;
use App\Modules\Kitchen\Domain\Models\KitchenStation;
use App\Modules\Tenancy\Application\Contracts\TenantCatalogReference;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use Illuminate\Support\Facades\DB;

final readonly class CreateKitchenStation
{
    public function __construct(
        private TenantPermissionGuard $permissions,
        private TenantCatalogReference $tenancy,
    ) {}

    public function handle(TenantRequestContext $context, KitchenStationInput $input): KitchenStation
    {
        $this->permissions->authorizeManageCatalog($context);

        return DB::transaction(function () use ($context, $input): KitchenStation {
            if (! $this->tenancy->activeOutletExists($context->tenantId, $input->outletId)) {
                throw KitchenException::outletNotFound();
            }

            $code = $this->normalizeCode($input->code);
            $exists = KitchenStation::query()
                ->where('tenant_id', $context->tenantId)
                ->where('outlet_id', $input->outletId)
                ->where('code', $code)
                ->exists();

            if ($exists) {
                throw KitchenException::stationCodeUnavailable();
            }

            if ($input->isFallback) {
                KitchenStation::query()
                    ->where('tenant_id', $context->tenantId)
                    ->where('outlet_id', $input->outletId)
                    ->update(['is_fallback' => false]);
            }

            return KitchenStation::query()->create([
                'tenant_id' => $context->tenantId,
                'outlet_id' => $input->outletId,
                'name' => trim($input->name),
                'code' => $code,
                'is_fallback' => $input->isFallback,
                'status' => KitchenStatus::Active,
            ]);
        });
    }

    private function normalizeCode(string $code): string
    {
        return mb_strtoupper(trim($code));
    }
}
