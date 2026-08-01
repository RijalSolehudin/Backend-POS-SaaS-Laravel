<?php

declare(strict_types=1);

namespace App\Modules\Sales\Domain\Enums;

enum CashMovementType: string
{
    case CashIn = 'cash_in';
    case CashOut = 'cash_out';
}
