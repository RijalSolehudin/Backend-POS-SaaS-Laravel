<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Actions;

use App\Modules\Catalog\Application\Data\AvailableCatalogProduct;
use App\Modules\Tenancy\Application\Data\PosOutletContext;

final readonly class GetAvailableOutletCatalogProduct
{
    public function __construct(private ListAvailableOutletCatalog $catalog) {}

    public function handle(PosOutletContext $context, string $productId): ?AvailableCatalogProduct
    {
        foreach ($this->catalog->handle($context) as $product) {
            if ($product->id === $productId) {
                return $product;
            }
        }

        return null;
    }
}
