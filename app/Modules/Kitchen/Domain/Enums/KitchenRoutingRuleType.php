<?php

declare(strict_types=1);

namespace App\Modules\Kitchen\Domain\Enums;

enum KitchenRoutingRuleType: string
{
    case Category = 'category';
    case Product = 'product';
    case Variant = 'variant';
}
