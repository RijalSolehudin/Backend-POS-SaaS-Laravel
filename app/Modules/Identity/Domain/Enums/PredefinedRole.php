<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Enums;

enum PredefinedRole: string
{
    case TenantOwner = 'tenant_owner';
    case OutletManager = 'outlet_manager';
    case Cashier = 'cashier';
}
