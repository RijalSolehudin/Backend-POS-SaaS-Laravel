<?php

declare(strict_types=1);

namespace App\Modules\Dining\Domain\Enums;

enum TableStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
