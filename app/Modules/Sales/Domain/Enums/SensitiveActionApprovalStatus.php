<?php

declare(strict_types=1);

namespace App\Modules\Sales\Domain\Enums;

enum SensitiveActionApprovalStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Consumed = 'consumed';
}
