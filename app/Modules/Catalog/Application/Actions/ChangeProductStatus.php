<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Actions;

use App\Modules\Catalog\Application\Exceptions\CatalogException;
use App\Modules\Catalog\Domain\Enums\ProductStatus;
use App\Modules\Catalog\Domain\Models\Product;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;

final readonly class ChangeProductStatus
{
    public function __construct(private TenantPermissionGuard $permissions) {}

    public function handle(TenantRequestContext $context, string $productId, ProductStatus $status): Product
    {
        $this->permissions->authorizeManageCatalog($context);

        $product = Product::query()
            ->where('tenant_id', $context->tenantId)
            ->whereKey($productId)
            ->first();

        if (! $product instanceof Product) {
            throw CatalogException::productNotFound();
        }

        $product->forceFill(['status' => $status])->save();

        return $product;
    }
}
