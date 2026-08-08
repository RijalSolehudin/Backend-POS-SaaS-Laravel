<?php

declare(strict_types=1);

namespace App\Modules\Dining\Domain\Enums;

enum TableStatus: string
{
    case AVAILABLE = 'available';
    case OCCUPIED = 'occupied';
    case RESERVED = 'reserved';
    case OUT_OF_SERVICE = 'out_of_service';
}

