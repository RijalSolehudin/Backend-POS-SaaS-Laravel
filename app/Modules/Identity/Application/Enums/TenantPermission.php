<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Enums;

enum TenantPermission: string
{
    case ManageOutlets = 'manage_outlets';
    case ManageOutletUsers = 'manage_outlet_users';
    case ManageTenantRoles = 'manage_tenant_roles';
    case RegisterDevices = 'register_devices';
    case ReassignDevices = 'reassign_devices';
    case RevokeDevices = 'revoke_devices';
    case OperatePos = 'operate_pos';
    case ReadCatalog = 'read_catalog';
    case ManageCatalog = 'manage_catalog';
}
