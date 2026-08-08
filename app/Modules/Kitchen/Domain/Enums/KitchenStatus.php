<?php

declare(strict_types=1);

namespace App\Modules\Kitchen\Domain\Enums;

enum KitchenStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
