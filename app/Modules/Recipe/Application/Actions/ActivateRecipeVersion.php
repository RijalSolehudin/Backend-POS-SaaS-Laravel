<?php

declare(strict_types=1);

namespace App\Modules\Recipe\Application\Actions;

use App\Modules\Recipe\Application\Exceptions\RecipeException;
use App\Modules\Recipe\Domain\Enums\RecipeVersionStatus;
use App\Modules\Recipe\Domain\Models\RecipeVersion;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use Illuminate\Support\Facades\DB;

final readonly class ActivateRecipeVersion
{
    public function __construct(private TenantPermissionGuard $permissions) {}

    public function handle(TenantRequestContext $context, string $versionId): RecipeVersion
    {
        $this->permissions->authorizeManageCatalog($context);

        return DB::transaction(function () use ($context, $versionId): RecipeVersion {
            $version = RecipeVersion::query()
                ->where('tenant_id', $context->tenantId)
                ->whereKey($versionId)
                ->lockForUpdate()
                ->first();

            if (! $version instanceof RecipeVersion) {
                throw RecipeException::versionNotFound();
            }

            if ($version->status !== RecipeVersionStatus::Draft) {
                throw RecipeException::invalidVersionState();
            }

            RecipeVersion::query()
                ->where('tenant_id', $context->tenantId)
                ->where('recipe_id', $version->recipe_id)
                ->where('status', RecipeVersionStatus::Active)
                ->update(['status' => RecipeVersionStatus::Archived]);

            $version->forceFill(['status' => RecipeVersionStatus::Active])->save();

            return $version->refresh();
        });
    }
}
