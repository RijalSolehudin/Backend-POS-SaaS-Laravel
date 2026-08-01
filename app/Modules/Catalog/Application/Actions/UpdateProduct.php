<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Actions;

use App\Modules\Catalog\Application\Data\ProductInput;
use App\Modules\Catalog\Application\Exceptions\CatalogException;
use App\Modules\Catalog\Domain\Models\Category;
use App\Modules\Catalog\Domain\Models\Product;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;

final readonly class UpdateProduct
{
    public function __construct(private TenantPermissionGuard $permissions) {}

    public function handle(TenantRequestContext $context, string $productId, ProductInput $input): Product
    {
        $this->permissions->authorizeManageCatalog($context);
        $this->ensureCategory($context, $input->categoryId);

        $product = Product::query()
            ->where('tenant_id', $context->tenantId)
            ->whereKey($productId)
            ->first();

        if (! $product instanceof Product) {
            throw CatalogException::productNotFound();
        }

        $sku = $this->normalizeSku($input->sku);
        $skuExists = Product::query()
            ->where('tenant_id', $context->tenantId)
            ->where('sku', $sku)
            ->whereKeyNot($productId)
            ->exists();

        if ($skuExists) {
            throw CatalogException::skuUnavailable();
        }

        $product->forceFill([
            'category_id' => $input->categoryId,
            'name' => trim($input->name),
            'sku' => $sku,
            'base_price_minor' => $input->basePriceMinor,
            'currency' => mb_strtoupper(trim($input->currency)),
            'display_order' => $input->displayOrder,
        ])->save();

        return $product;
    }

    private function ensureCategory(TenantRequestContext $context, string $categoryId): void
    {
        $exists = Category::query()
            ->where('tenant_id', $context->tenantId)
            ->whereKey($categoryId)
            ->exists();

        if (! $exists) {
            throw CatalogException::categoryNotFound();
        }
    }

    private function normalizeSku(string $sku): string
    {
        return mb_strtoupper(trim($sku));
    }
}
