<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Actions;

use App\Modules\Inventory\Application\Data\LowStockItemView;
use App\Modules\Inventory\Application\Exceptions\InventoryException;
use App\Modules\Inventory\Application\Services\DecimalQuantity;
use App\Modules\Inventory\Domain\Enums\InventoryStatus;
use App\Modules\Inventory\Domain\Models\InventoryBalance;
use App\Modules\Inventory\Domain\Models\InventoryItem;
use App\Modules\Inventory\Domain\Models\InventoryItemOutletSetting;
use App\Modules\Inventory\Domain\Models\InventoryUnit;
use App\Modules\Tenancy\Application\Contracts\TenantCatalogReference;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;

final readonly class ListLowStockItems
{
    public function __construct(
        private TenantCatalogReference $tenancy,
        private TenantPermissionGuard $permissions,
        private DecimalQuantity $quantity,
    ) {}

    /**
     * @return list<LowStockItemView>
     */
    public function handle(TenantRequestContext $context, string $outletId): array
    {
        $this->permissions->authorizeManageCatalog($context);

        if (! $this->tenancy->activeOutletExists($context->tenantId, $outletId)) {
            throw InventoryException::outletNotFound();
        }

        $settings = InventoryItemOutletSetting::query()
            ->where('tenant_id', $context->tenantId)
            ->where('outlet_id', $outletId)
            ->where('status', InventoryStatus::Active)
            ->get();

        $items = InventoryItem::query()
            ->where('tenant_id', $context->tenantId)
            ->whereIn('id', $settings->pluck('item_id')->all())
            ->get()
            ->keyBy('id');
        $units = InventoryUnit::query()
            ->where('tenant_id', $context->tenantId)
            ->whereIn('id', $items->pluck('unit_id')->all())
            ->get()
            ->keyBy('id');
        $balances = InventoryBalance::query()
            ->where('tenant_id', $context->tenantId)
            ->where('outlet_id', $outletId)
            ->whereIn('item_id', $settings->pluck('item_id')->all())
            ->get()
            ->keyBy('item_id');

        $lowStock = [];

        foreach ($settings as $setting) {
            $threshold = (string) $setting->low_stock_threshold_quantity;

            if ($this->quantity->toScaled($threshold) <= 0) {
                continue;
            }

            $balance = $balances->get($setting->item_id);
            $currentQuantity = $balance instanceof InventoryBalance ? (string) $balance->quantity : '0.000';

            if ($this->quantity->toScaled($currentQuantity) > $this->quantity->toScaled($threshold)) {
                continue;
            }

            $item = $items->get($setting->item_id);

            if (! $item instanceof InventoryItem || $item->status !== InventoryStatus::Active) {
                continue;
            }

            $unit = $units->get($item->unit_id);

            if (! $unit instanceof InventoryUnit) {
                continue;
            }

            $lowStock[] = new LowStockItemView(
                tenantId: $context->tenantId,
                outletId: $outletId,
                itemId: $item->id,
                itemName: $item->name,
                sku: $item->sku,
                unitSymbol: $unit->symbol,
                quantity: $currentQuantity,
                thresholdQuantity: $threshold,
                totalCostMinor: $balance instanceof InventoryBalance ? $balance->total_cost_minor : 0,
                currency: $balance instanceof InventoryBalance ? $balance->currency : ($this->tenancy->tenant($context->tenantId)->currency ?? 'IDR'),
            );
        }

        return $lowStock;
    }
}
