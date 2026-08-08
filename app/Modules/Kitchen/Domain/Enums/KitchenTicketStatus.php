<?php

declare(strict_types=1);

namespace App\Modules\Kitchen\Domain\Enums;

enum KitchenTicketStatus: string
{
    case Queued = 'queued';
    case Preparing = 'preparing';
    case Ready = 'ready';
    case Served = 'served';
    case Cancelled = 'cancelled';
}
