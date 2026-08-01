<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Actions;

use App\Modules\Catalog\Application\Exceptions\CatalogException;
use App\Modules\Catalog\Domain\Enums\CategoryStatus;
use App\Modules\Catalog\Domain\Models\Category;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;

final readonly class ChangeCategoryStatus
{
    public function __construct(private TenantPermissionGuard $permissions) {}

    public function handle(TenantRequestContext $context, string $categoryId, CategoryStatus $status): Category
    {
        $this->permissions->authorizeManageCatalog($context);

        $category = Category::query()
            ->where('tenant_id', $context->tenantId)
            ->whereKey($categoryId)
            ->first();

        if (! $category instanceof Category) {
            throw CatalogException::categoryNotFound();
        }

        $category->forceFill(['status' => $status])->save();

        return $category;
    }
}
