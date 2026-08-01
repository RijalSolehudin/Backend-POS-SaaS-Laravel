<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Services;

use App\Modules\Identity\Application\Enums\TenantPermission;

final class PredefinedTenantRolePolicy
{
    /** @var array<string, list<TenantPermission>> */
    private const PERMISSIONS_BY_ROLE = [
        'tenant_owner' => [
            TenantPermission::ManageOutlets,
            TenantPermission::ManageOutletUsers,
            TenantPermission::ManageTenantRoles,
            TenantPermission::RegisterDevices,
            TenantPermission::ReassignDevices,
            TenantPermission::RevokeDevices,
            TenantPermission::OperatePos,
            TenantPermission::ReadCatalog,
            TenantPermission::ManageCatalog,
        ],
        'outlet_manager' => [
            TenantPermission::RegisterDevices,
            TenantPermission::OperatePos,
            TenantPermission::ReadCatalog,
        ],
        'cashier' => [
            TenantPermission::OperatePos,
            TenantPermission::ReadCatalog,
        ],
    ];

    /**
     * @return list<string>
     */
    public function roles(): array
    {
        return array_keys(self::PERMISSIONS_BY_ROLE);
    }

    public function isPredefinedRole(string $role): bool
    {
        return array_key_exists($role, self::PERMISSIONS_BY_ROLE);
    }

    /**
     * @param  list<string>  $roles
     */
    public function allows(array $roles, TenantPermission $permission): bool
    {
        foreach ($roles as $role) {
            if (in_array($permission, self::PERMISSIONS_BY_ROLE[$role] ?? [], true)) {
                return true;
            }
        }

        return false;
    }
}
