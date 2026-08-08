<?php

declare(strict_types=1);

namespace App\Modules\Reservation\Domain\Enums;

enum ReservationStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Seated = 'seated';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';
}
