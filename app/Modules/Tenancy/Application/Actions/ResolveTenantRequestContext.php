<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Actions;

use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Domain\Enums\TenantStatus;
use App\Modules\Tenancy\Domain\Models\Tenant;
use App\Modules\Tenancy\Domain\Models\TenantMembership;

final class ResolveTenantRequestContext
{
    public function handle(string $userId): ?TenantRequestContext
    {
        $membership = TenantMembership::query()
            ->where('user_id', $userId)
            ->first();

        if ($membership === null) {
            return null;
        }

        $tenant = Tenant::query()->find($membership->tenant_id);

        if (! $tenant instanceof Tenant || $tenant->status !== TenantStatus::Active) {
            return null;
        }

        return new TenantRequestContext(
            tenantId: (string) $tenant->getKey(),
            userId: $userId,
            membershipType: $membership->membership_type,
        );
    }
}
