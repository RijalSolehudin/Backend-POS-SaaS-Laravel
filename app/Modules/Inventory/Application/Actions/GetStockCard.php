<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Actions;

use App\Modules\Inventory\Application\Data\StockCardEntry;
use App\Modules\Inventory\Application\Exceptions\InventoryException;
use App\Modules\Inventory\Domain\Models\InventoryItem;
use App\Modules\Inventory\Domain\Models\InventoryStockMovement;
use App\Modules\Tenancy\Application\Contracts\TenantCatalogReference;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use Carbon\CarbonImmutable;

final readonly class GetStockCard
{
    public function __construct(
        private TenantCatalogReference $tenancy,
        private TenantPermissionGuard $permissions,
    ) {}

    /**
     * @return list<StockCardEntry>
     */
    public function handle(
        TenantRequestContext $context,
        string $outletId,
        string $itemId,
        ?CarbonImmutable $from = null,
        ?CarbonImmutable $to = null,
        ?string $sourceType = null,
    ): array {
        $this->permissions->authorizeManageCatalog($context);
        $this->ensureItem($context, $itemId);

        if (! $this->tenancy->activeOutletExists($context->tenantId, $outletId)) {
            throw InventoryException::outletNotFound();
        }

        $query = InventoryStockMovement::query()
            ->where('tenant_id', $context->tenantId)
            ->where('outlet_id', $outletId)
            ->where('item_id', $itemId)
            ->orderBy('occurred_at')
            ->orderBy('id');

        if ($from instanceof CarbonImmutable) {
            $query->where('occurred_at', '>=', $from);
        }

        if ($to instanceof CarbonImmutable) {
            $query->where('occurred_at', '<=', $to);
        }

        if ($sourceType !== null && trim($sourceType) !== '') {
            $query->where('source_type', trim($sourceType));
        }

        return array_values($query->get()
            ->map(fn (InventoryStockMovement $movement): StockCardEntry => new StockCardEntry(
                movementId: $movement->id,
                movementType: $movement->movement_type->value,
                sourceType: $movement->source_type,
                sourceId: $movement->source_id,
                quantity: (string) $movement->quantity,
                unitCostMinor: $movement->unit_cost_minor,
                totalCostMinor: $movement->total_cost_minor,
                currency: $movement->currency,
                balanceQuantityAfter: (string) $movement->balance_quantity_after,
                balanceTotalCostMinorAfter: $movement->balance_total_cost_minor_after,
                reason: $movement->reason,
                occurredAt: $movement->occurred_at,
            ))
            ->all());
    }

    private function ensureItem(TenantRequestContext $context, string $itemId): void
    {
        $exists = InventoryItem::query()
            ->where('tenant_id', $context->tenantId)
            ->whereKey($itemId)
            ->exists();

        if (! $exists) {
            throw InventoryException::itemNotFound();
        }
    }
}
