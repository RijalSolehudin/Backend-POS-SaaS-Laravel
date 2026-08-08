<?php

declare(strict_types=1);

namespace App\Modules\PaymentsGateway\Domain\Enums;

enum PaymentIntentStatus: string
{
    case Pending = 'pending';
    case RequiresAction = 'requires_action';
    case Paid = 'paid';
    case Failed = 'failed';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
}
