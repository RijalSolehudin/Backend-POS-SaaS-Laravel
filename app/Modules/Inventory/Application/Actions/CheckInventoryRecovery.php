<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Actions;

use App\Modules\Inventory\Application\Data\InventoryRecoveryDiscrepancy;
use App\Modules\Inventory\Application\Services\DecimalQuantity;
use App\Modules\Inventory\Domain\Enums\TransferStatus;
use App\Modules\Inventory\Domain\Models\InventoryBalance;
use App\Modules\Inventory\Domain\Models\InventoryStockMovement;
use App\Modules\Inventory\Domain\Models\InventoryTransfer;
use App\Modules\Inventory\Domain\Models\InventoryTransferLine;

final readonly class CheckInventoryRecovery
{
    public function __construct(private DecimalQuantity $quantity) {}

    /**
     * @return list<InventoryRecoveryDiscrepancy>
     */
    public function handle(?string $tenantId = null, ?string $outletId = null, ?string $itemId = null): array
    {
        $keys = $this->scopeKeys($tenantId, $outletId, $itemId);
        $discrepancies = [];

        foreach ($keys as $key) {
            $expected = $this->replay($key['tenant_id'], $key['outlet_id'], $key['item_id']);
            $balance = InventoryBalance::query()
                ->where('tenant_id', $key['tenant_id'])
                ->where('outlet_id', $key['outlet_id'])
                ->where('item_id', $key['item_id'])
                ->first();
            $actualQuantity = $balance instanceof InventoryBalance ? (string) $balance->quantity : '0.000';
            $actualTotalCostMinor = $balance instanceof InventoryBalance ? $balance->total_cost_minor : 0;

            if ($expected['quantity'] === $actualQuantity && $expected['total_cost_minor'] === $actualTotalCostMinor) {
                continue;
            }

            $discrepancies[] = new InventoryRecoveryDiscrepancy(
                tenantId: $key['tenant_id'],
                outletId: $key['outlet_id'],
                itemId: $key['item_id'],
                expectedQuantity: $expected['quantity'],
                actualQuantity: $actualQuantity,
                expectedTotalCostMinor: $expected['total_cost_minor'],
                actualTotalCostMinor: $actualTotalCostMinor,
                inTransitQuantity: $this->inTransitQuantity($key['tenant_id'], $key['outlet_id'], $key['item_id']),
            );
        }

        return $discrepancies;
    }

    /**
     * @return list<array{tenant_id: string, outlet_id: string, item_id: string}>
     */
    private function scopeKeys(?string $tenantId, ?string $outletId, ?string $itemId): array
    {
        $movementKeys = InventoryStockMovement::query()
            ->select(['tenant_id', 'outlet_id', 'item_id'])
            ->when($tenantId !== null, fn ($query) => $query->where('tenant_id', $tenantId))
            ->when($outletId !== null, fn ($query) => $query->where('outlet_id', $outletId))
            ->when($itemId !== null, fn ($query) => $query->where('item_id', $itemId))
            ->distinct()
            ->get()
            ->map(fn (InventoryStockMovement $movement): array => [
                'tenant_id' => $movement->tenant_id,
                'outlet_id' => $movement->outlet_id,
                'item_id' => $movement->item_id,
            ])
            ->all();

        $balanceKeys = InventoryBalance::query()
            ->select(['tenant_id', 'outlet_id', 'item_id'])
            ->when($tenantId !== null, fn ($query) => $query->where('tenant_id', $tenantId))
            ->when($outletId !== null, fn ($query) => $query->where('outlet_id', $outletId))
            ->when($itemId !== null, fn ($query) => $query->where('item_id', $itemId))
            ->distinct()
            ->get()
            ->map(fn (InventoryBalance $balance): array => [
                'tenant_id' => $balance->tenant_id,
                'outlet_id' => $balance->outlet_id,
                'item_id' => $balance->item_id,
            ])
            ->all();

        $seen = [];
        $keys = [];

        foreach ([...$movementKeys, ...$balanceKeys] as $key) {
            $hash = $key['tenant_id'].'|'.$key['outlet_id'].'|'.$key['item_id'];

            if (isset($seen[$hash])) {
                continue;
            }

            $seen[$hash] = true;
            $keys[] = $key;
        }

        return $keys;
    }

    /**
     * @return array{quantity: string, total_cost_minor: int}
     */
    private function replay(string $tenantId, string $outletId, string $itemId): array
    {
        $quantityScaled = 0;
        $totalCostMinor = 0;

        $movements = InventoryStockMovement::query()
            ->where('tenant_id', $tenantId)
            ->where('outlet_id', $outletId)
            ->where('item_id', $itemId)
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();

        foreach ($movements as $movement) {
            $movementQuantityScaled = $this->quantity->toScaled((string) $movement->quantity);
            $newQuantityScaled = $quantityScaled + $movementQuantityScaled;

            if ($movementQuantityScaled >= 0) {
                $totalCostMinor += $movement->total_cost_minor;
                $quantityScaled = $newQuantityScaled;

                continue;
            }

            $outboundCostMinor = $quantityScaled > 0
                ? intdiv((abs($movementQuantityScaled) * $totalCostMinor) + intdiv($quantityScaled, 2), $quantityScaled)
                : 0;

            $quantityScaled = $newQuantityScaled;
            $totalCostMinor -= $outboundCostMinor;

            if ($quantityScaled === 0) {
                $totalCostMinor = 0;
            }
        }

        return [
            'quantity' => $this->quantity->format($quantityScaled),
            'total_cost_minor' => $totalCostMinor,
        ];
    }

    private function inTransitQuantity(string $tenantId, string $outletId, string $itemId): string
    {
        $transferIds = InventoryTransfer::query()
            ->where('tenant_id', $tenantId)
            ->where('source_outlet_id', $outletId)
            ->where('status', TransferStatus::Dispatched)
            ->pluck('id')
            ->all();

        if ($transferIds === []) {
            return '0.000';
        }

        $scaled = 0;

        foreach (InventoryTransferLine::query()
            ->where('tenant_id', $tenantId)
            ->where('item_id', $itemId)
            ->whereIn('transfer_id', $transferIds)
            ->get(['quantity']) as $line) {
            $scaled += $this->quantity->toScaled((string) $line->quantity);
        }

        return $this->quantity->format($scaled);
    }
}
