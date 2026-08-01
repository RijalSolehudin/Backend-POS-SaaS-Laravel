<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Data;

final readonly class ProductInput
{
    public function __construct(
        public string $name,
        public string $sku,
        public string $categoryId,
        public int $basePriceMinor,
        public string $currency,
        public int $displayOrder = 0,
    ) {}
}
