<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\Enums;

enum InventoryStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
