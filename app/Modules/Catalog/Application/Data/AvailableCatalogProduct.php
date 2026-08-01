<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Data;

final readonly class AvailableCatalogProduct
{
    public function __construct(
        public string $id,
        public string $sku,
        public string $name,
        public string $categoryId,
        public string $categoryName,
        public ?string $parentCategoryId,
        public ?string $parentCategoryName,
        public int $priceMinor,
        public string $currency,
    ) {}
}
