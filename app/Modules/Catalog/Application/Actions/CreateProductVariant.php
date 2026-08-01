<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Actions;

use App\Modules\Catalog\Application\Data\ProductVariantInput;
use App\Modules\Catalog\Application\Exceptions\CatalogException;
use App\Modules\Catalog\Domain\Enums\ProductStatus;
use App\Modules\Catalog\Domain\Models\Product;
use App\Modules\Catalog\Domain\Models\ProductVariant;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use Illuminate\Support\Facades\DB;

final readonly class CreateProductVariant
{
    public function __construct(private TenantPermissionGuard $permissions) {}

    public function handle(TenantRequestContext $context, ProductVariantInput $input): ProductVariant
    {
        $this->permissions->authorizeManageCatalog($context);

        return DB::transaction(function () use ($context, $input): ProductVariant {
            $product = $this->product($context, $input->productId);
            $sku = $this->normalizeSku($input->sku);
            $this->ensureSkuAvailable($context, $sku);
            $this->ensureCurrencyMatches($product, $input->currency);

            if ($input->isDefault) {
                ProductVariant::query()
                    ->where('tenant_id', $context->tenantId)
                    ->where('product_id', $product->id)
                    ->update(['is_default' => false]);
            }

            return ProductVariant::query()->create([
                'tenant_id' => $context->tenantId,
                'product_id' => $product->id,
                'name' => trim($input->name),
                'sku' => $sku,
                'price_minor' => $input->priceMinor,
                'currency' => mb_strtoupper(trim($input->currency)),
                'is_default' => $input->isDefault,
                'display_order' => $input->displayOrder,
                'status' => ProductStatus::Active,
            ]);
        });
    }

    private function product(TenantRequestContext $context, string $productId): Product
    {
        $product = Product::query()
            ->where('tenant_id', $context->tenantId)
            ->whereKey($productId)
            ->lockForUpdate()
            ->first();

        if (! $product instanceof Product) {
            throw CatalogException::productNotFound();
        }

        return $product;
    }

    private function ensureSkuAvailable(TenantRequestContext $context, string $sku): void
    {
        $exists = Product::query()
            ->where('tenant_id', $context->tenantId)
            ->where('sku', $sku)
            ->exists()
            || ProductVariant::query()
                ->where('tenant_id', $context->tenantId)
                ->where('sku', $sku)
                ->exists();

        if ($exists) {
            throw CatalogException::skuUnavailable();
        }
    }

    private function ensureCurrencyMatches(Product $product, string $currency): void
    {
        if ($product->currency !== mb_strtoupper(trim($currency))) {
            throw CatalogException::currencyMismatch();
        }
    }

    private function normalizeSku(string $sku): string
    {
        return mb_strtoupper(trim($sku));
    }
}
