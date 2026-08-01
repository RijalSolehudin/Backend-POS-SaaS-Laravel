<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Infrastructure\Catalog;

use App\Modules\Tenancy\Application\Contracts\TenantCatalogReference;
use App\Modules\Tenancy\Application\Data\OutletCatalogSummary;
use App\Modules\Tenancy\Application\Data\TenantCatalogSummary;
use App\Modules\Tenancy\Domain\Enums\OutletStatus;
use App\Modules\Tenancy\Domain\Enums\TenantStatus;
use App\Modules\Tenancy\Domain\Models\Outlet;
use App\Modules\Tenancy\Domain\Models\Tenant;

final class DatabaseTenantCatalogReference implements TenantCatalogReference
{
    public function tenant(string $tenantId): ?TenantCatalogSummary
    {
        $tenant = Tenant::query()->whereKey($tenantId)->first();

        if (! $tenant instanceof Tenant || $tenant->status !== TenantStatus::Active) {
            return null;
        }

        return new TenantCatalogSummary(
            tenantId: (string) $tenant->getKey(),
            name: $tenant->name,
            currency: $tenant->currency,
        );
    }

    public function activeOutletExists(string $tenantId, string $outletId): bool
    {
        return Outlet::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($outletId)
            ->where('status', OutletStatus::Active)
            ->exists();
    }

    public function activeOutlets(string $tenantId): array
    {
        return array_values(Outlet::query()
            ->where('tenant_id', $tenantId)
            ->where('status', OutletStatus::Active)
            ->orderBy('name')
            ->get()
            ->map(fn (Outlet $outlet): OutletCatalogSummary => new OutletCatalogSummary(
                outletId: (string) $outlet->getKey(),
                name: $outlet->name,
            ))
            ->all());
    }
}
