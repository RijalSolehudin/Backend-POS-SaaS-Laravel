<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Domain\Enums;

enum OutletStatus: string
{
    case Active = 'active';
    case Disabled = 'disabled';
}
