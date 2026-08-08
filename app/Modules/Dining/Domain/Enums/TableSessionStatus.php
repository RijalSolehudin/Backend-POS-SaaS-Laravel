<?php

declare(strict_types=1);

namespace App\Modules\Dining\Domain\Enums;

enum TableSessionStatus: string
{
    case Open = 'open';
    case Merged = 'merged';
    case Transferred = 'transferred';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
}
