<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Actions;

use App\Modules\Catalog\Application\Data\ProductInput;
use App\Modules\Catalog\Application\Exceptions\CatalogException;
use App\Modules\Catalog\Domain\Enums\ProductStatus;
use App\Modules\Catalog\Domain\Models\Category;
use App\Modules\Catalog\Domain\Models\Product;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;

final readonly class CreateProduct
{
    public function __construct(private TenantPermissionGuard $permissions) {}

    public function handle(TenantRequestContext $context, ProductInput $input): Product
    {
        $this->permissions->authorizeManageCatalog($context);
        $this->ensureCategory($context, $input->categoryId);
        $this->ensureSkuAvailable($context, $input->sku);

        return Product::query()->create([
            'tenant_id' => $context->tenantId,
            'category_id' => $input->categoryId,
            'name' => trim($input->name),
            'sku' => $this->normalizeSku($input->sku),
            'base_price_minor' => $input->basePriceMinor,
            'currency' => mb_strtoupper(trim($input->currency)),
            'display_order' => $input->displayOrder,
            'status' => ProductStatus::Active,
        ]);
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

    private function ensureSkuAvailable(TenantRequestContext $context, string $sku): void
    {
        $exists = Product::query()
            ->where('tenant_id', $context->tenantId)
            ->where('sku', $this->normalizeSku($sku))
            ->exists();

        if ($exists) {
            throw CatalogException::skuUnavailable();
        }
    }

    private function normalizeSku(string $sku): string
    {
        return mb_strtoupper(trim($sku));
    }
}
