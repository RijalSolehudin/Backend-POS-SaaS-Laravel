<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Infrastructure\Identity;

use App\Modules\Identity\Application\Contracts\TenantAccessResolver;
use App\Modules\Identity\Application\Data\TenantAccess;
use App\Modules\Tenancy\Domain\Enums\TenantStatus;
use App\Modules\Tenancy\Domain\Models\Tenant;
use App\Modules\Tenancy\Domain\Models\TenantMembership;

final class DatabaseTenantAccessResolver implements TenantAccessResolver
{
    public function forUser(string $userId): ?TenantAccess
    {
        $membership = TenantMembership::query()->where('user_id', $userId)->first();

        if ($membership === null) {
            return null;
        }

        $tenant = Tenant::query()->find($membership->tenant_id);

        if ($tenant === null) {
            return null;
        }

        return new TenantAccess(
            tenantId: $membership->tenant_id,
            tenantActive: $tenant->status === TenantStatus::Active,
        );
    }
}
