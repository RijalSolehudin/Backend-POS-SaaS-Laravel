<?php

declare(strict_types=1);

namespace App\Modules\Recipe\Application\Actions;

use App\Modules\Recipe\Application\Data\RecipeInput;
use App\Modules\Recipe\Application\Exceptions\RecipeException;
use App\Modules\Recipe\Domain\Models\Recipe;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;

final readonly class UpdateRecipe
{
    public function __construct(private TenantPermissionGuard $permissions) {}

    public function handle(TenantRequestContext $context, string $recipeId, RecipeInput $input): Recipe
    {
        $this->permissions->authorizeManageCatalog($context);
        $recipe = Recipe::query()->where('tenant_id', $context->tenantId)->whereKey($recipeId)->first();

        if (! $recipe instanceof Recipe) {
            throw RecipeException::notFound();
        }

        $sku = mb_strtoupper(trim($input->sku));
        $exists = Recipe::query()
            ->where('tenant_id', $context->tenantId)
            ->where('sku', $sku)
            ->whereKeyNot($recipeId)
            ->exists();

        if ($exists) {
            throw RecipeException::skuUnavailable();
        }

        $recipe->forceFill([
            'name' => trim($input->name),
            'sku' => $sku,
            'requires_recipe' => $input->requiresRecipe,
        ])->save();

        return $recipe;
    }
}
