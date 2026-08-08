<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence;

use App\Modules\Catalog\Application\Contracts\KitchenCatalogReference;
use App\Modules\Catalog\Application\Data\KitchenCatalogItem;
use App\Modules\Catalog\Domain\Models\Product;
use App\Modules\Catalog\Domain\Models\ProductVariant;

final class DatabaseKitchenCatalogReference implements KitchenCatalogReference
{
    public function item(string $tenantId, string $productId, ?string $variantId): ?KitchenCatalogItem
    {
        $product = Product::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($productId)
            ->first();

        if (! $product instanceof Product) {
            return null;
        }

        if ($variantId !== null) {
            $variantExists = ProductVariant::query()
                ->where('tenant_id', $tenantId)
                ->where('product_id', $productId)
                ->whereKey($variantId)
                ->exists();

            if (! $variantExists) {
                return null;
            }
        }

        return new KitchenCatalogItem(
            tenantId: $tenantId,
            productId: $product->id,
            variantId: $variantId,
            categoryId: $product->category_id,
        );
    }
}
