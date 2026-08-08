<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Data;

final readonly class KitchenCatalogItem
{
    public function __construct(
        public string $tenantId,
        public string $productId,
        public ?string $variantId,
        public string $categoryId,
    ) {}
}
