<?php

declare(strict_types=1);

namespace App\Modules\OrderingChannel\Domain\Enums;

enum QrSessionStatus: string
{
    case Active = 'active';
    case Expired = 'expired';
    case Revoked = 'revoked';
}
