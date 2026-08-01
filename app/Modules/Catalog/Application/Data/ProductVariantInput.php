<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Data;

final readonly class ProductVariantInput
{
    public function __construct(
        public string $productId,
        public string $name,
        public string $sku,
        public int $priceMinor,
        public string $currency,
        public bool $isDefault = false,
        public int $displayOrder = 0,
    ) {}
}
