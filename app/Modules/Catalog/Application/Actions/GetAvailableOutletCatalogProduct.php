<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Actions;

use App\Modules\Catalog\Application\Data\AvailableCatalogProduct;
use App\Modules\Catalog\Domain\Enums\CategoryStatus;
use App\Modules\Catalog\Domain\Enums\ProductStatus;
use App\Modules\Catalog\Domain\Models\Product;
use App\Modules\Tenancy\Application\Data\PosOutletContext;
use Illuminate\Support\Facades\DB;

final readonly class GetAvailableOutletCatalogProduct
{
    public function handle(PosOutletContext $context, string $productId): ?AvailableCatalogProduct
    {
        /** @var object{id: string, sku: string, name: string, category_id: string, category_name: string, parent_category_id: string|null, parent_category_name: string|null, base_price_minor: int, outlet_price_minor: int|null, currency: string}|null $row */
        $row = Product::query()
            ->from('catalog_products')
            ->join('catalog_categories', 'catalog_categories.id', '=', 'catalog_products.category_id')
            ->leftJoin('catalog_categories as parent_categories', 'parent_categories.id', '=', 'catalog_categories.parent_id')
            ->join('catalog_product_outlet_availabilities', 'catalog_product_outlet_availabilities.product_id', '=', 'catalog_products.id')
            ->where('catalog_products.tenant_id', $context->tenantId)
            ->where('catalog_products.id', $productId)
            ->where('catalog_categories.tenant_id', $context->tenantId)
            ->where(function ($query): void {
                $query
                    ->whereNull('catalog_categories.parent_id')
                    ->orWhere('parent_categories.status', CategoryStatus::Active);
            })
            ->where('catalog_product_outlet_availabilities.tenant_id', $context->tenantId)
            ->where('catalog_product_outlet_availabilities.outlet_id', $context->outletId)
            ->where('catalog_product_outlet_availabilities.available', true)
            ->where('catalog_products.status', ProductStatus::Active)
            ->where('catalog_categories.status', CategoryStatus::Active)
            ->first([
                'catalog_products.id',
                'catalog_products.sku',
                'catalog_products.name',
                'catalog_products.category_id',
                DB::raw('catalog_categories.name as category_name'),
                DB::raw('parent_categories.id as parent_category_id'),
                DB::raw('parent_categories.name as parent_category_name'),
                'catalog_products.base_price_minor',
                DB::raw('catalog_product_outlet_availabilities.price_minor as outlet_price_minor'),
                'catalog_products.currency',
            ]);

        if ($row === null) {
            return null;
        }

        return new AvailableCatalogProduct(
            id: $row->id,
            sku: $row->sku,
            name: $row->name,
            categoryId: $row->category_id,
            categoryName: $row->category_name,
            parentCategoryId: $row->parent_category_id,
            parentCategoryName: $row->parent_category_name,
            priceMinor: $row->outlet_price_minor ?? $row->base_price_minor,
            currency: $row->currency,
        );
    }
}
