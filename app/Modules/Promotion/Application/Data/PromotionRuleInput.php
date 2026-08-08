<?php

declare(strict_types=1);

namespace App\Modules\Promotion\Application\Data;

use App\Modules\Promotion\Domain\Enums\PromotionDiscountType;

final readonly class PromotionRuleInput
{
    public function __construct(
        public string $name,
        public string $code,
        public PromotionDiscountType $discountType,
        public int $discountValue,
        public ?string $outletId = null,
    ) {}
}
