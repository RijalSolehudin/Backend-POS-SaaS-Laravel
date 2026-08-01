<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Infrastructure\Approvals;

use App\Modules\Tenancy\Application\Contracts\SensitiveActionApprovalAuthority;
use App\Modules\Tenancy\Application\Contracts\TenantUserDirectory;
use App\Modules\Tenancy\Domain\Models\OutletUserAssignment;

final readonly class DatabaseSensitiveActionApprovalAuthority implements SensitiveActionApprovalAuthority
{
    public function __construct(private TenantUserDirectory $users) {}

    public function canApproveForOutlet(string $tenantId, string $outletId, string $userId): bool
    {
        $user = $this->users->findForTenant($tenantId, $userId);

        if ($user === null || ! $user->active) {
            return false;
        }

        if (in_array('tenant_owner', $user->roles, true)) {
            return true;
        }

        if (! in_array('outlet_manager', $user->roles, true)) {
            return false;
        }

        return OutletUserAssignment::query()
            ->where('tenant_id', $tenantId)
            ->where('outlet_id', $outletId)
            ->where('user_id', $userId)
            ->exists();
    }
}
