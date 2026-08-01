<?php

declare(strict_types=1);

namespace App\Modules\Recipe\Application\Actions;

use App\Modules\Inventory\Application\Actions\RecordStockMovement;
use App\Modules\Inventory\Application\Data\StockMovementInput;
use App\Modules\Inventory\Application\Exceptions\InventoryException;
use App\Modules\Inventory\Application\Services\DecimalQuantity;
use App\Modules\Inventory\Domain\Enums\StockMovementType;
use App\Modules\Inventory\Domain\Models\InventoryBalance;
use App\Modules\Recipe\Application\Exceptions\RecipeException;
use App\Modules\Recipe\Domain\Enums\RecipeVersionStatus;
use App\Modules\Recipe\Domain\Models\RecipeIngredient;
use App\Modules\Recipe\Domain\Models\RecipeSalesDeduction;
use App\Modules\Recipe\Domain\Models\RecipeVariantMapping;
use App\Modules\Recipe\Domain\Models\RecipeVersion;
use App\Modules\Sales\Domain\Models\Order;
use App\Modules\Sales\Domain\Models\OrderItem;

final readonly class DeductOrderInventory
{
    public function __construct(
        private RecordStockMovement $movements,
        private DecimalQuantity $quantity,
    ) {}

    public function handle(Order $order, string $idempotencyKey): void
    {
        foreach (OrderItem::query()->where('tenant_id', $order->tenant_id)->where('order_id', $order->id)->orderBy('id')->get() as $orderItem) {
            $this->deductItem($order, $orderItem, $idempotencyKey);
        }
    }

    private function deductItem(Order $order, OrderItem $orderItem, string $idempotencyKey): void
    {
        if (RecipeSalesDeduction::query()->where('tenant_id', $order->tenant_id)->where('order_item_id', $orderItem->id)->exists()) {
            return;
        }

        if ($orderItem->variant_id === null) {
            return;
        }

        $mapping = RecipeVariantMapping::query()
            ->where('tenant_id', $order->tenant_id)
            ->where('variant_id', $orderItem->variant_id)
            ->first();

        if (! $mapping instanceof RecipeVariantMapping) {
            return;
        }

        if (! $mapping->requires_recipe) {
            return;
        }

        if ($mapping->recipe_version_id === null) {
            throw RecipeException::mappingRequired();
        }

        $version = RecipeVersion::query()->where('tenant_id', $order->tenant_id)->whereKey($mapping->recipe_version_id)->first();

        if (! $version instanceof RecipeVersion) {
            throw RecipeException::versionNotFound();
        }

        if ($version->status !== RecipeVersionStatus::Active) {
            throw RecipeException::invalidVersionState();
        }

        $snapshot = [];
        $totalCostMinor = 0;

        foreach (RecipeIngredient::query()->where('tenant_id', $order->tenant_id)->where('recipe_version_id', $version->id)->get() as $ingredient) {
            $usageQuantity = $this->usageQuantity((string) $ingredient->quantity, (string) $orderItem->quantity);

            try {
                $movement = $this->movements->handle(new StockMovementInput(
                    tenantId: $order->tenant_id,
                    outletId: $order->outlet_id,
                    itemId: $ingredient->inventory_item_id,
                    unitId: $ingredient->unit_id,
                    actorUserId: $order->user_id,
                    movementType: StockMovementType::SalesDeduction,
                    sourceType: 'sales_order_item',
                    sourceId: $orderItem->id,
                    quantity: '-'.$usageQuantity,
                    unitCostMinor: null,
                    totalCostMinor: null,
                    currency: $this->currency($order->tenant_id, $order->outlet_id, $ingredient->inventory_item_id, $order->currency),
                    reason: 'Sales order recipe deduction',
                    idempotencyKey: $this->movementIdempotencyKey($idempotencyKey, $orderItem, $ingredient),
                ));
            } catch (InventoryException $exception) {
                if ($exception->errorCode() === 'INVENTORY_INSUFFICIENT_STOCK') {
                    throw RecipeException::insufficientStock();
                }

                throw $exception;
            }

            $totalCostMinor += abs($movement->total_cost_minor);
            $snapshot[] = [
                'inventory_item_id' => $ingredient->inventory_item_id,
                'unit_id' => $ingredient->unit_id,
                'quantity' => $usageQuantity,
                'movement_id' => $movement->id,
                'total_cost_minor' => abs($movement->total_cost_minor),
            ];
        }

        RecipeSalesDeduction::query()->create([
            'tenant_id' => $order->tenant_id,
            'outlet_id' => $order->outlet_id,
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'recipe_version_id' => $version->id,
            'snapshot' => [
                'recipe_version_id' => $version->id,
                'version_number' => $version->version_number,
                'order_item_quantity' => (string) $orderItem->quantity,
                'ingredients' => $snapshot,
            ],
            'total_cost_minor' => $totalCostMinor,
            'currency' => $order->currency,
        ]);
    }

    private function movementIdempotencyKey(string $idempotencyKey, OrderItem $orderItem, RecipeIngredient $ingredient): string
    {
        return 'recipe:'.hash('xxh3', $idempotencyKey.'|'.$orderItem->id.'|'.$ingredient->id);
    }

    private function usageQuantity(string $ingredientQuantity, string $orderQuantity): string
    {
        $scaled = intdiv(
            $this->quantity->toScaled($ingredientQuantity) * $this->quantity->toScaled($orderQuantity),
            1000,
        );

        return $this->quantity->format($scaled);
    }

    private function currency(string $tenantId, string $outletId, string $itemId, string $fallback): string
    {
        $balance = InventoryBalance::query()
            ->where('tenant_id', $tenantId)
            ->where('outlet_id', $outletId)
            ->where('item_id', $itemId)
            ->first();

        return $balance instanceof InventoryBalance ? $balance->currency : $fallback;
    }
}
