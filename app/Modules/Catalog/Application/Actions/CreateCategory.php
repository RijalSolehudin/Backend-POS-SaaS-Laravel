<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Actions;

use App\Modules\Catalog\Application\Data\CategoryInput;
use App\Modules\Catalog\Application\Exceptions\CatalogException;
use App\Modules\Catalog\Domain\Enums\CategoryStatus;
use App\Modules\Catalog\Domain\Models\Category;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;

final readonly class CreateCategory
{
    public function __construct(private TenantPermissionGuard $permissions) {}

    public function handle(TenantRequestContext $context, CategoryInput $input): Category
    {
        $this->permissions->authorizeManageCatalog($context);
        $this->ensureParentCategory($context, $input->parentId);

        return Category::query()->create([
            'tenant_id' => $context->tenantId,
            'parent_id' => $input->parentId,
            'name' => trim($input->name),
            'display_order' => $input->displayOrder,
            'status' => CategoryStatus::Active,
        ]);
    }

    private function ensureParentCategory(TenantRequestContext $context, ?string $parentId): void
    {
        if ($parentId === null) {
            return;
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
