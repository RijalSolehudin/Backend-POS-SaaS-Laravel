<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Contracts;

use App\Modules\Tenancy\Application\Data\OutletCatalogSummary;
use App\Modules\Tenancy\Application\Data\TenantCatalogSummary;

interface TenantCatalogReference
{
    public function tenant(string $tenantId): ?TenantCatalogSummary;

    public function activeOutletExists(string $tenantId, string $outletId): bool;

    /**
     * @return list<OutletCatalogSummary>
     */
    public function activeOutlets(string $tenantId): array;
}
