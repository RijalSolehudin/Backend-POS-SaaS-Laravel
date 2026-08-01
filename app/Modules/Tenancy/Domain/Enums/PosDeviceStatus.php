<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Domain\Enums;

enum PosDeviceStatus: string
{
    case Active = 'active';
    case Revoked = 'revoked';
}
