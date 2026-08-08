<?php

declare(strict_types=1);

namespace App\Modules\Promotion\Domain\Enums;

enum PromotionDiscountType: string
{
    case Fixed = 'fixed';
    case Percentage = 'percentage';
}
