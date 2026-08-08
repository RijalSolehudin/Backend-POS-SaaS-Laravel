<?php

declare(strict_types=1);

namespace App\Modules\Kitchen\Application\Actions;

use App\Modules\Catalog\Application\Contracts\KitchenCatalogReference;
use App\Modules\Kitchen\Application\Data\KitchenRoutingResult;
use App\Modules\Kitchen\Domain\Enums\KitchenRoutingRuleType;
use App\Modules\Kitchen\Domain\Enums\KitchenStatus;
use App\Modules\Kitchen\Domain\Models\KitchenRoutingRule;
use App\Modules\Kitchen\Domain\Models\KitchenStation;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;

final readonly class ResolveKitchenRouting
{
    public function __construct(private KitchenCatalogReference $catalog) {}

    public function handle(
        TenantRequestContext $context,
        string $outletId,
        string $productId,
        ?string $variantId,
        ?string $categoryId = null,
    ): KitchenRoutingResult {
        $catalogItem = $this->catalog->item($context->tenantId, $productId, $variantId);
        $category = $categoryId ?? $catalogItem?->categoryId;

        $candidates = [];

        if ($variantId !== null) {
            $candidates[] = [KitchenRoutingRuleType::Variant, $variantId, 10];
        }

        $candidates[] = [KitchenRoutingRuleType::Product, $productId, 20];

        if ($category !== null) {
            $candidates[] = [KitchenRoutingRuleType::Category, $category, 30];
        }

        foreach ($candidates as [$type, $referenceId]) {
            $rule = KitchenRoutingRule::query()
                ->where('tenant_id', $context->tenantId)
                ->where('outlet_id', $outletId)
                ->where('status', KitchenStatus::Active)
                ->where('rule_type', $type)
                ->where('catalog_reference_id', $referenceId)
                ->orderBy('priority')
                ->first();

            if ($rule instanceof KitchenRoutingRule) {
                return new KitchenRoutingResult((string) $rule->station_id, false, false);
            }
        }

        $fallback = KitchenStation::query()
            ->where('tenant_id', $context->tenantId)
            ->where('outlet_id', $outletId)
            ->where('status', KitchenStatus::Active)
            ->where('is_fallback', true)
            ->first();

        if ($fallback instanceof KitchenStation) {
            return new KitchenRoutingResult($fallback->id, true, false);
        }

        return new KitchenRoutingResult(null, false, true);
    }
}
