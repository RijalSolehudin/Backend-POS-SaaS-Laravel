<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Domain\Enums;

enum SecondFactorMethod: string
{
    case Totp = 'totp';
    case RecoveryCode = 'recovery_code';
}
