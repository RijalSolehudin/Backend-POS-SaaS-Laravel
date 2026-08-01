<?php

declare(strict_types=1);

namespace App\Modules\Recipe\Application\Actions;

use App\Modules\Catalog\Domain\Models\ProductVariant;
use App\Modules\Recipe\Application\Exceptions\RecipeException;
use App\Modules\Recipe\Domain\Enums\RecipeVersionStatus;
use App\Modules\Recipe\Domain\Models\RecipeVariantMapping;
use App\Modules\Recipe\Domain\Models\RecipeVersion;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;

final readonly class SetRecipeVariantMapping
{
    public function __construct(private TenantPermissionGuard $permissions) {}

    public function handle(TenantRequestContext $context, string $variantId, ?string $recipeVersionId, bool $requiresRecipe): RecipeVariantMapping
    {
        $this->permissions->authorizeManageCatalog($context);
        $variant = ProductVariant::query()->where('tenant_id', $context->tenantId)->whereKey($variantId)->first();

        if (! $variant instanceof ProductVariant) {
            throw RecipeException::crossTenantReference();
        }

        if ($requiresRecipe) {
            $version = RecipeVersion::query()
                ->where('tenant_id', $context->tenantId)
                ->whereKey($recipeVersionId)
                ->first();

            if (! $version instanceof RecipeVersion) {
                throw RecipeException::versionNotFound();
            }

            if ($version->status !== RecipeVersionStatus::Active) {
                throw RecipeException::invalidVersionState();
            }
        }

        return RecipeVariantMapping::query()->updateOrCreate(
            ['tenant_id' => $context->tenantId, 'variant_id' => $variantId],
            ['recipe_version_id' => $requiresRecipe ? $recipeVersionId : null, 'requires_recipe' => $requiresRecipe],
        );
    }
}
