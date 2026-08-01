<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Actions;

use App\Modules\Catalog\Application\Data\AvailableCatalogProduct;
use App\Modules\Catalog\Application\Data\AvailableCatalogVariant;
use App\Modules\Catalog\Application\Data\AvailableModifierGroup;
use App\Modules\Catalog\Application\Data\AvailableModifierOption;
use App\Modules\Catalog\Domain\Enums\CategoryStatus;
use App\Modules\Catalog\Domain\Enums\ProductStatus;
use App\Modules\Catalog\Domain\Models\ModifierGroup;
use App\Modules\Catalog\Domain\Models\ModifierOption;
use App\Modules\Catalog\Domain\Models\Product;
use App\Modules\Catalog\Domain\Models\ProductVariant;
use App\Modules\Tenancy\Application\Data\PosOutletContext;
use Illuminate\Support\Facades\DB;

final readonly class ListAvailableOutletCatalog
{
    /**
     * @return list<AvailableCatalogProduct>
     */
    public function handle(PosOutletContext $context): array
    {
        /** @var list<object{id: string, sku: string, name: string, category_id: string, category_name: string, parent_category_id: string|null, parent_category_name: string|null, base_price_minor: int, outlet_price_minor: int|null, currency: string}> $rows */
        $rows = Product::query()
            ->from('catalog_products')
            ->join('catalog_categories', 'catalog_categories.id', '=', 'catalog_products.category_id')
            ->leftJoin('catalog_categories as parent_categories', 'parent_categories.id', '=', 'catalog_categories.parent_id')
            ->join('catalog_product_outlet_availabilities', 'catalog_product_outlet_availabilities.product_id', '=', 'catalog_products.id')
            ->where('catalog_products.tenant_id', $context->tenantId)
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
            ->orderByRaw('COALESCE(parent_categories.display_order, catalog_categories.display_order)')
            ->orderByRaw('COALESCE(parent_categories.name, catalog_categories.name)')
            ->orderBy('catalog_categories.display_order')
            ->orderBy('catalog_categories.name')
            ->orderBy('catalog_products.display_order')
            ->orderBy('catalog_products.name')
            ->get([
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
            ])
            ->all();

        return array_map(
            fn (object $row): AvailableCatalogProduct => new AvailableCatalogProduct(
                id: $row->id,
                sku: $row->sku,
                name: $row->name,
                categoryId: $row->category_id,
                categoryName: $row->category_name,
                parentCategoryId: $row->parent_category_id,
                parentCategoryName: $row->parent_category_name,
                priceMinor: $row->outlet_price_minor ?? $row->base_price_minor,
                currency: $row->currency,
                variants: $this->variants($context, $row),
            ),
            $rows,
        );
    }

    /**
     * @param  object{id: string, sku: string, name: string, base_price_minor: int, outlet_price_minor: int|null, currency: string}  $row
     * @return list<AvailableCatalogVariant>
     */
    private function variants(PosOutletContext $context, object $row): array
    {
        /** @var list<object{id: string, sku: string, name: string, price_minor: int, currency: string, is_default: bool}> $variants */
        $variants = ProductVariant::query()
            ->where('tenant_id', $context->tenantId)
            ->where('product_id', $row->id)
            ->where('status', ProductStatus::Active)
            ->orderByDesc('is_default')
            ->orderBy('display_order')
            ->orderBy('name')
            ->get(['id', 'sku', 'name', 'price_minor', 'currency', 'is_default'])
            ->all();

        if ($variants === []) {
            return [
                new AvailableCatalogVariant(
                    id: $row->id,
                    sku: $row->sku,
                    name: $row->name,
                    priceMinor: $row->outlet_price_minor ?? $row->base_price_minor,
                    currency: $row->currency,
                    isDefault: true,
                    modifierGroups: $this->modifierGroups($context, $row->id, null),
                ),
            ];
        }

        return array_map(
            fn (object $variant): AvailableCatalogVariant => new AvailableCatalogVariant(
                id: $variant->id,
                sku: $variant->sku,
                name: $variant->name,
                priceMinor: $variant->price_minor,
                currency: $variant->currency,
                isDefault: (bool) $variant->is_default,
                modifierGroups: $this->modifierGroups($context, $row->id, $variant->id),
            ),
            $variants,
        );
    }

    /**
     * @return list<AvailableModifierGroup>
     */
    private function modifierGroups(PosOutletContext $context, string $productId, ?string $variantId): array
    {
        /** @var list<object{id: string, name: string, required: bool, min_selection: int, max_selection: int}> $groups */
        $groups = ModifierGroup::query()
            ->where('tenant_id', $context->tenantId)
            ->where('product_id', $productId)
            ->where(function ($query) use ($variantId): void {
                $query->whereNull('variant_id');

                if ($variantId !== null) {
                    $query->orWhere('variant_id', $variantId);
                }
            })
            ->where('status', ProductStatus::Active)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get(['id', 'name', 'required', 'min_selection', 'max_selection'])
            ->all();

        return array_map(
            fn (object $group): AvailableModifierGroup => new AvailableModifierGroup(
                id: $group->id,
                name: $group->name,
                required: (bool) $group->required,
                minSelection: $group->min_selection,
                maxSelection: $group->max_selection,
                options: $this->modifierOptions($context, $group->id),
            ),
            $groups,
        );
    }

    /**
     * @return list<AvailableModifierOption>
     */
    private function modifierOptions(PosOutletContext $context, string $groupId): array
    {
        /** @var list<object{id: string, name: string, price_delta_minor: int, currency: string}> $options */
        $options = ModifierOption::query()
            ->where('tenant_id', $context->tenantId)
            ->where('group_id', $groupId)
            ->where('status', ProductStatus::Active)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get(['id', 'name', 'price_delta_minor', 'currency'])
            ->all();

        return array_map(
            fn (object $option): AvailableModifierOption => new AvailableModifierOption(
                id: $option->id,
                name: $option->name,
                priceDeltaMinor: $option->price_delta_minor,
                currency: $option->currency,
            ),
            $options,
        );
    }
}
