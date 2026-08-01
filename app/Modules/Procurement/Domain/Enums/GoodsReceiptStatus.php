<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Domain\Enums;

enum GoodsReceiptStatus: string
{
    case Recorded = 'recorded';
    case Voided = 'voided';
}
