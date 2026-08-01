<?php

declare(strict_types=1);

namespace App\Modules\Recipe\Application\Actions;

use App\Modules\Recipe\Application\Exceptions\RecipeException;
use App\Modules\Recipe\Domain\Enums\RecipeStatus;
use App\Modules\Recipe\Domain\Models\Recipe;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;

final readonly class ChangeRecipeStatus
{
    public function __construct(private TenantPermissionGuard $permissions) {}

    public function handle(TenantRequestContext $context, string $recipeId, RecipeStatus $status): Recipe
    {
        $this->permissions->authorizeManageCatalog($context);
        $recipe = Recipe::query()->where('tenant_id', $context->tenantId)->whereKey($recipeId)->first();

        if (! $recipe instanceof Recipe) {
            throw RecipeException::notFound();
        }

        $recipe->forceFill(['status' => $status])->save();

        return $recipe;
    }
}
