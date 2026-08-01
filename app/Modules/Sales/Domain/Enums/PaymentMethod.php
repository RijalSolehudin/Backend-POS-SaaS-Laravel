<?php

declare(strict_types=1);

namespace App\Modules\Sales\Domain\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case ManualNonCash = 'manual_non_cash';
}
