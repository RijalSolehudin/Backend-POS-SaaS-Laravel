<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Contracts;

use App\Modules\Catalog\Application\Data\KitchenCatalogItem;

interface KitchenCatalogReference
{
    public function item(string $tenantId, string $productId, ?string $variantId): ?KitchenCatalogItem;
}
