<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\Enums;

enum StockMovementType: string
{
    case OpeningBalance = 'opening_balance';
    case AdjustmentIncrease = 'adjustment_increase';
    case AdjustmentDecrease = 'adjustment_decrease';
    case Waste = 'waste';
    case TransferOut = 'transfer_out';
    case TransferIn = 'transfer_in';
    case Reversal = 'reversal';
}
