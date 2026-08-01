<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Actions;

use App\Modules\Inventory\Application\Data\InventoryBalanceView;
use App\Modules\Inventory\Application\Exceptions\InventoryException;
use App\Modules\Inventory\Application\Services\DecimalQuantity;
use App\Modules\Inventory\Domain\Models\InventoryBalance;
use App\Modules\Inventory\Domain\Models\InventoryItem;
use App\Modules\Tenancy\Application\Contracts\TenantCatalogReference;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;

final readonly class GetInventoryBalance
{
    public function __construct(
        private TenantCatalogReference $tenancy,
        private TenantPermissionGuard $permissions,
        private DecimalQuantity $quantity,
    ) {}

    public function handle(TenantRequestContext $context, string $outletId, string $itemId): InventoryBalanceView
    {
        $this->permissions->authorizeManageCatalog($context);
        $item = $this->item($context, $itemId);

        if (! $this->tenancy->activeOutletExists($context->tenantId, $outletId)) {
            throw InventoryException::outletNotFound();
        }

        $tenant = $this->tenancy->tenant($context->tenantId);
        $currency = $tenant->currency ?? 'IDR';

        $balance = InventoryBalance::query()
            ->where('tenant_id', $context->tenantId)
            ->where('outlet_id', $outletId)
            ->where('item_id', $itemId)
            ->first();

        if (! $balance instanceof InventoryBalance) {
            return new InventoryBalanceView(
                tenantId: $context->tenantId,
                outletId: $outletId,
                itemId: $itemId,
                unitId: $item->unit_id,
                quantity: '0.000',
                totalCostMinor: 0,
                currency: $currency,
                averageCostMinor: null,
                inTransitQuantity: '0.000',
            );
        }

        $quantityScaled = $this->quantity->toScaled((string) $balance->quantity);

        return new InventoryBalanceView(
            tenantId: $balance->tenant_id,
            outletId: $balance->outlet_id,
            itemId: $balance->item_id,
            unitId: $balance->unit_id,
            quantity: (string) $balance->quantity,
            totalCostMinor: $balance->total_cost_minor,
            currency: $balance->currency,
            averageCostMinor: $this->quantity->unitCostMinor($balance->total_cost_minor, $quantityScaled),
            inTransitQuantity: '0.000',
        );
    }

    private function item(TenantRequestContext $context, string $itemId): InventoryItem
    {
        $item = InventoryItem::query()
            ->where('tenant_id', $context->tenantId)
            ->whereKey($itemId)
            ->first();

        if (! $item instanceof InventoryItem) {
            throw InventoryException::itemNotFound();
        }

        return $item;
    }
}
