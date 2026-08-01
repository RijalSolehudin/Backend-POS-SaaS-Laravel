<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Domain\Enums;

enum SupplierStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
