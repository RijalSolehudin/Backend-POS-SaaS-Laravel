<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Actions;

use App\Modules\Catalog\Application\Data\CategoryInput;
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

        return Category::query()->create([
            'tenant_id' => $context->tenantId,
            'name' => trim($input->name),
            'status' => CategoryStatus::Active,
        ]);
    }
}
