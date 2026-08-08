<?php

declare(strict_types=1);

namespace App\Modules\Promotion\Domain\Enums;

enum PromotionStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
