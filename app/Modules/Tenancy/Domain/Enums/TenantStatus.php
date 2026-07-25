<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Domain\Enums;

enum TenantStatus: string
{
    case Active = 'active';
    case Disabled = 'disabled';
}
