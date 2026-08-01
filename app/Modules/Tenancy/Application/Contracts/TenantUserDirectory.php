<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Contracts;

use App\Modules\Tenancy\Application\Data\TenantUserSummary;

interface TenantUserDirectory
{
    public function findForTenant(string $tenantId, string $userId): ?TenantUserSummary;

    /**
     * @return list<TenantUserSummary>
     */
    public function listForTenant(string $tenantId): array;
}
