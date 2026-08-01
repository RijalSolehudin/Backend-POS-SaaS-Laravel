<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Actions;

use App\Modules\Catalog\Application\Exceptions\CatalogException;
use App\Modules\Catalog\Domain\Models\Product;
use App\Modules\Catalog\Domain\Models\ProductOutletAvailability;
use App\Modules\Tenancy\Application\Contracts\TenantCatalogReference;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;

final readonly class SetOutletAvailability
{
    public function __construct(
        private TenantPermissionGuard $permissions,
        private TenantCatalogReference $tenancy,
    ) {}

    public function handle(
        TenantRequestContext $context,
        string $productId,
        string $outletId,
        bool $available,
        ?int $priceMinor,
    ): ProductOutletAvailability {
        $this->permissions->authorizeManageCatalog($context);

        $product = Product::query()
            ->where('tenant_id', $context->tenantId)
            ->whereKey($productId)
            ->first();

        if (! $product instanceof Product) {
            throw CatalogException::productNotFound();
        }

        if (! $this->tenancy->activeOutletExists($context->tenantId, $outletId)) {
            throw CatalogException::outletNotFound();
        }

        /** @var ProductOutletAvailability $availability */
        $availability = ProductOutletAvailability::query()->updateOrCreate(
            [
                'tenant_id' => $context->tenantId,
                'product_id' => $productId,
                'outlet_id' => $outletId,
            ],
            [
                'available' => $available,
                'price_minor' => $priceMinor,
            ],
        );

        return $availability;
    }
}
