<?php

declare(strict_types=1);

namespace App\Modules\Recipe\Application\Actions;

use App\Modules\Inventory\Application\Services\DecimalQuantity;
use App\Modules\Inventory\Domain\Models\InventoryBalance;
use App\Modules\Inventory\Domain\Models\InventoryItem;
use App\Modules\Recipe\Application\Data\RecipeVersionInput;
use App\Modules\Recipe\Application\Exceptions\RecipeException;
use App\Modules\Recipe\Domain\Enums\RecipeVersionStatus;
use App\Modules\Recipe\Domain\Models\Recipe;
use App\Modules\Recipe\Domain\Models\RecipeIngredient;
use App\Modules\Recipe\Domain\Models\RecipeVersion;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use Illuminate\Support\Facades\DB;

final readonly class CreateRecipeVersion
{
    public function __construct(
        private TenantPermissionGuard $permissions,
        private DecimalQuantity $quantity,
    ) {}

    public function handle(TenantRequestContext $context, RecipeVersionInput $input, ?string $costOutletId = null): RecipeVersion
    {
        $this->permissions->authorizeManageCatalog($context);

        return DB::transaction(function () use ($context, $input, $costOutletId): RecipeVersion {
            $recipe = Recipe::query()->where('tenant_id', $context->tenantId)->whereKey($input->recipeId)->first();

            if (! $recipe instanceof Recipe) {
                throw RecipeException::notFound();
            }

            $version = RecipeVersion::query()->create([
                'tenant_id' => $context->tenantId,
                'recipe_id' => $recipe->id,
                'version_number' => $this->nextVersionNumber($context, $recipe->id),
                'status' => RecipeVersionStatus::Draft,
                'yield_quantity' => $this->quantity->normalize($input->yieldQuantity),
                'yield_percent' => $input->yieldPercent,
                'currency' => mb_strtoupper(trim($input->currency)),
                'cost_minor' => 0,
            ]);
            $costMinor = 0;

            foreach ($input->ingredients as $line) {
                $item = InventoryItem::query()
                    ->where('tenant_id', $context->tenantId)
                    ->whereKey($line->inventoryItemId)
                    ->first();

                if (! $item instanceof InventoryItem) {
                    throw RecipeException::crossTenantReference();
                }

                $quantity = $this->quantity->normalize($line->quantity);
                $unitCost = $costOutletId === null ? null : $this->averageCost($context->tenantId, $costOutletId, $item->id);
                $lineCost = $unitCost === null
                    ? null
                    : intdiv(($this->quantity->toScaled($quantity) * $unitCost) + 500, 1000);
                $costMinor += $lineCost ?? 0;

                RecipeIngredient::query()->create([
                    'tenant_id' => $context->tenantId,
                    'recipe_version_id' => $version->id,
                    'inventory_item_id' => $item->id,
                    'unit_id' => $item->unit_id,
                    'quantity' => $quantity,
                    'unit_cost_minor_snapshot' => $unitCost,
                    'total_cost_minor_snapshot' => $lineCost,
                ]);
            }

            $version->forceFill(['cost_minor' => $costMinor])->save();

            return $version->refresh();
        });
    }

    private function nextVersionNumber(TenantRequestContext $context, string $recipeId): int
    {
        $latest = RecipeVersion::query()
            ->where('tenant_id', $context->tenantId)
            ->where('recipe_id', $recipeId)
            ->max('version_number');

        return is_int($latest) ? $latest + 1 : ((int) $latest) + 1;
    }

    private function averageCost(string $tenantId, string $outletId, string $itemId): ?int
    {
        $balance = InventoryBalance::query()
            ->where('tenant_id', $tenantId)
            ->where('outlet_id', $outletId)
            ->where('item_id', $itemId)
            ->first();

        if (! $balance instanceof InventoryBalance) {
            return null;
        }

        return $this->quantity->unitCostMinor($balance->total_cost_minor, $this->quantity->toScaled((string) $balance->quantity));
    }
}
