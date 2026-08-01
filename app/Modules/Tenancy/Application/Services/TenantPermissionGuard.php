<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Services;

use App\Modules\Identity\Application\Contracts\TenantRoleAssignments;
use App\Modules\Identity\Application\Enums\TenantPermission;
use App\Modules\Identity\Application\Services\PredefinedTenantRolePolicy;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Exceptions\TenancyException;
use App\Modules\Tenancy\Domain\Models\OutletUserAssignment;

final readonly class TenantPermissionGuard
{
    public function __construct(
        private TenantRoleAssignments $roles,
        private PredefinedTenantRolePolicy $policy,
    ) {}

    public function authorize(TenantRequestContext $context, TenantPermission $permission): void
    {
        if ($context->isOwner()) {
            return;
        }

        if ($this->policy->allows($this->roles->rolesForUser($context->userId), $permission)) {
            return;
        }

        throw TenancyException::forbidden();
    }

    public function authorizeManageOutlets(TenantRequestContext $context): void
    {
        $this->authorize($context, TenantPermission::ManageOutlets);
    }

    public function authorizeManageOutletUsers(TenantRequestContext $context): void
    {
        $this->authorize($context, TenantPermission::ManageOutletUsers);
    }

    public function authorizeManageTenantRoles(TenantRequestContext $context): void
    {
        $this->authorize($context, TenantPermission::ManageTenantRoles);
    }

    public function canManageDevices(TenantRequestContext $context): bool
    {
        if ($context->isOwner()) {
            return true;
        }

        $roles = $this->roles->rolesForUser($context->userId);

        return $this->policy->allows($roles, TenantPermission::RegisterDevices)
            || $this->policy->allows($roles, TenantPermission::ReassignDevices)
            || $this->policy->allows($roles, TenantPermission::RevokeDevices);
    }

    public function authorizeRegisterDevice(TenantRequestContext $context, string $outletId): void
    {
        if ($context->isOwner()) {
            return;
        }

        $roles = $this->roles->rolesForUser($context->userId);

        if (
            $this->policy->allows($roles, TenantPermission::RegisterDevices)
            && $this->isAssignedToOutlet($context, $outletId)
        ) {
            return;
        }

        throw TenancyException::forbidden();
    }

    public function authorizeReassignDevices(TenantRequestContext $context): void
    {
        $this->authorize($context, TenantPermission::ReassignDevices);
    }

    public function authorizeRevokeDevices(TenantRequestContext $context): void
    {
        $this->authorize($context, TenantPermission::RevokeDevices);
    }

    public function authorizeManageCatalog(TenantRequestContext $context): void
    {
        $this->authorize($context, TenantPermission::ManageCatalog);
    }

    public function authorizeReadCatalog(TenantRequestContext $context, string $outletId): void
    {
        if ($context->isOwner()) {
            return;
        }

        $roles = $this->roles->rolesForUser($context->userId);

        if (
            $this->policy->allows($roles, TenantPermission::ReadCatalog)
            && $this->isAssignedToOutlet($context, $outletId)
        ) {
            return;
        }

        throw TenancyException::forbidden();
    }

    public function authorizeOperatePos(TenantRequestContext $context, string $outletId): void
    {
        if ($context->isOwner()) {
            return;
        }

        $roles = $this->roles->rolesForUser($context->userId);

        if (
            $this->policy->allows($roles, TenantPermission::OperatePos)
            && $this->isAssignedToOutlet($context, $outletId)
        ) {
            return;
        }

        throw TenancyException::forbidden();
    }

    private function isAssignedToOutlet(TenantRequestContext $context, string $outletId): bool
    {
        return OutletUserAssignment::query()
            ->where('tenant_id', $context->tenantId)
            ->where('outlet_id', $outletId)
            ->where('user_id', $context->userId)
            ->exists();
    }
}
