<?php

declare(strict_types=1);

namespace App\Modules\Sales\Domain\Enums;

enum OrderStatus: string
{
    case Draft = 'draft';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Voided = 'voided';
}
