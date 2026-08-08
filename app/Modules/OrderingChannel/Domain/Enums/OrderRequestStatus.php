<?php

declare(strict_types=1);

namespace App\Modules\OrderingChannel\Domain\Enums;

enum OrderRequestStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';
    case Expired = 'expired';
}
