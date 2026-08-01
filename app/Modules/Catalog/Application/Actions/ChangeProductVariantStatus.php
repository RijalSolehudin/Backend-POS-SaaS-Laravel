<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Actions;

use App\Modules\Catalog\Application\Exceptions\CatalogException;
use App\Modules\Catalog\Domain\Enums\ProductStatus;
use App\Modules\Catalog\Domain\Models\ProductVariant;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;

final readonly class ChangeProductVariantStatus
{
    public function __construct(private TenantPermissionGuard $permissions) {}

    public function handle(TenantRequestContext $context, string $variantId, ProductStatus $status): ProductVariant
    {
        $this->permissions->authorizeManageCatalog($context);

        $variant = ProductVariant::query()
            ->where('tenant_id', $context->tenantId)
            ->whereKey($variantId)
            ->first();

        if (! $variant instanceof ProductVariant) {
            throw CatalogException::variantNotFound();
        }

        $variant->forceFill(['status' => $status])->save();

        return $variant;
    }
}
