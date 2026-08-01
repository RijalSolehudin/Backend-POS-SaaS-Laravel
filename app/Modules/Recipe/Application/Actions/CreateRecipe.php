<?php

declare(strict_types=1);

namespace App\Modules\Recipe\Application\Actions;

use App\Modules\Recipe\Application\Data\RecipeInput;
use App\Modules\Recipe\Application\Exceptions\RecipeException;
use App\Modules\Recipe\Domain\Enums\RecipeStatus;
use App\Modules\Recipe\Domain\Models\Recipe;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;

final readonly class CreateRecipe
{
    public function __construct(private TenantPermissionGuard $permissions) {}

    public function handle(TenantRequestContext $context, RecipeInput $input): Recipe
    {
        $this->permissions->authorizeManageCatalog($context);
        $this->ensureSkuAvailable($context, $input->sku);

        return Recipe::query()->create([
            'tenant_id' => $context->tenantId,
            'name' => trim($input->name),
            'sku' => $this->normalizeSku($input->sku),
            'status' => RecipeStatus::Active,
            'requires_recipe' => $input->requiresRecipe,
        ]);
    }

    private function ensureSkuAvailable(TenantRequestContext $context, string $sku): void
    {
        $exists = Recipe::query()
            ->where('tenant_id', $context->tenantId)
            ->where('sku', $this->normalizeSku($sku))
            ->exists();

        if ($exists) {
            throw RecipeException::skuUnavailable();
        }
    }

    private function normalizeSku(string $sku): string
    {
        return mb_strtoupper(trim($sku));
    }
}
