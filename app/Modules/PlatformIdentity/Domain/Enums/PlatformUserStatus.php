<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Domain\Enums;

enum PlatformUserStatus: string
{
    case PendingMfaSetup = 'pending_mfa_setup';
    case Active = 'active';
    case Suspended = 'suspended';
}
