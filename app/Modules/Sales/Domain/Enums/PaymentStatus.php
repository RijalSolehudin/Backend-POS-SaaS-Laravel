<?php

declare(strict_types=1);

namespace App\Modules\Sales\Domain\Enums;

enum PaymentStatus: string
{
    case Recorded = 'recorded';
    case Voided = 'voided';
}
