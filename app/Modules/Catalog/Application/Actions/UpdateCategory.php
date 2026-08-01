<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Actions;

use App\Modules\Catalog\Application\Data\CategoryInput;
use App\Modules\Catalog\Application\Exceptions\CatalogException;
use App\Modules\Catalog\Domain\Models\Category;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;

final readonly class UpdateCategory
{
    public function __construct(private TenantPermissionGuard $permissions) {}

    public function handle(TenantRequestContext $context, string $categoryId, CategoryInput $input): Category
    {
        $this->permissions->authorizeManageCatalog($context);

        $category = Category::query()
            ->where('tenant_id', $context->tenantId)
            ->whereKey($categoryId)
            ->first();

        if (! $category instanceof Category) {
            throw CatalogException::categoryNotFound();
        }

        $this->ensureParentCategory($context, $category, $input->parentId);

        $category->forceFill([
            'parent_id' => $input->parentId,
            'name' => trim($input->name),
            'display_order' => $input->displayOrder,
        ])->save();

        return $category;
    }

    private function ensureParentCategory(TenantRequestContext $context, Category $category, ?string $parentId): void
    {
        if ($parentId === null) {
            return;
        }

        if ($parentId === $category->id) {
            throw CatalogException::invalidCategoryParent();
        }

        $exists = Category::query()
            ->where('tenant_id', $context->tenantId)
            ->whereNull('parent_id')
            ->whereKey($parentId)
            ->exists();

        if (! $exists) {
            throw CatalogException::invalidCategoryParent();
        }
    }
}
