<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Actions;

use App\Modules\Inventory\Application\Data\StockMovementInput;
use App\Modules\Inventory\Application\Exceptions\InventoryException;
use App\Modules\Inventory\Application\Services\DecimalQuantity;
use App\Modules\Inventory\Domain\Enums\StockMovementType;
use App\Modules\Inventory\Domain\Models\InventoryBalance;
use App\Modules\Inventory\Domain\Models\InventoryStockMovement;
use Illuminate\Database\QueryException;

final readonly class RecordStockMovement
{
    public function __construct(private DecimalQuantity $quantity) {}

    public function handle(StockMovementInput $input): InventoryStockMovement
    {
        $this->ensureBalanceRow($input);

        $balance = InventoryBalance::query()
            ->where('tenant_id', $input->tenantId)
            ->where('outlet_id', $input->outletId)
            ->where('item_id', $input->itemId)
            ->lockForUpdate()
            ->firstOrFail();

        if ($balance->unit_id !== $input->unitId) {
            throw InventoryException::unitMismatch();
        }

        if ($balance->currency !== $input->currency) {
            throw InventoryException::currencyMismatch();
        }

        $movementQuantityScaled = $this->quantity->toScaled($input->quantity);
        $oldQuantityScaled = $this->quantity->toScaled((string) $balance->quantity);
        $oldTotalCostMinor = (int) $balance->total_cost_minor;
        $newQuantityScaled = $oldQuantityScaled + $movementQuantityScaled;

        if ($newQuantityScaled < 0) {
            throw InventoryException::insufficientStock();
        }

        $movementTotalCostMinor = $this->movementTotalCostMinor(
            $input,
            $movementQuantityScaled,
            $oldQuantityScaled,
            $oldTotalCostMinor,
        );
        $newTotalCostMinor = $oldTotalCostMinor + $movementTotalCostMinor;

        if ($newQuantityScaled === 0) {
            $newTotalCostMinor = 0;
        }

        $balance->forceFill([
            'quantity' => $this->quantity->format($newQuantityScaled),
            'total_cost_minor' => $newTotalCostMinor,
        ])->save();

        return InventoryStockMovement::query()->create([
            'tenant_id' => $input->tenantId,
            'outlet_id' => $input->outletId,
            'item_id' => $input->itemId,
            'unit_id' => $input->unitId,
            'actor_user_id' => $input->actorUserId,
            'movement_type' => $input->movementType,
            'source_type' => $input->sourceType,
            'source_id' => $input->sourceId,
            'opening_balance_key' => $input->movementType === StockMovementType::OpeningBalance
                ? $this->openingBalanceKey($input->tenantId, $input->outletId, $input->itemId)
                : null,
            'quantity' => $this->quantity->format($movementQuantityScaled),
            'unit_cost_minor' => $input->unitCostMinor,
            'total_cost_minor' => $movementTotalCostMinor,
            'currency' => $input->currency,
            'balance_quantity_after' => $this->quantity->format($newQuantityScaled),
            'balance_total_cost_minor_after' => $newTotalCostMinor,
            'reason' => $input->reason,
            'idempotency_key' => $input->idempotencyKey,
            'occurred_at' => now(),
        ]);
    }

    private function ensureBalanceRow(StockMovementInput $input): void
    {
        try {
            InventoryBalance::query()->firstOrCreate(
                [
                    'tenant_id' => $input->tenantId,
                    'outlet_id' => $input->outletId,
                    'item_id' => $input->itemId,
                ],
                [
                    'unit_id' => $input->unitId,
                    'quantity' => '0.000',
                    'total_cost_minor' => 0,
                    'currency' => $input->currency,
                ],
            );
        } catch (QueryException) {
            // The unique index is the race-condition backstop; the next query locks the existing row.
        }
    }

    private function movementTotalCostMinor(
        StockMovementInput $input,
        int $movementQuantityScaled,
        int $oldQuantityScaled,
        int $oldTotalCostMinor,
    ): int {
        if ($movementQuantityScaled >= 0) {
            return $input->totalCostMinor ?? 0;
        }

        if ($oldQuantityScaled <= 0) {
            throw InventoryException::insufficientStock();
        }

        $outboundQuantityScaled = abs($movementQuantityScaled);
        $outboundCostMinor = intdiv(
            ($outboundQuantityScaled * $oldTotalCostMinor) + intdiv($oldQuantityScaled, 2),
            $oldQuantityScaled,
        );

        return -$outboundCostMinor;
    }

    private function openingBalanceKey(string $tenantId, string $outletId, string $itemId): string
    {
        return $tenantId.'|'.$outletId.'|'.$itemId;
    }
}
