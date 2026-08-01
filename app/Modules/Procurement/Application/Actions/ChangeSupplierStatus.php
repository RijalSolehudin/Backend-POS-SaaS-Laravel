<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Application\Actions;

use App\Modules\Procurement\Application\Exceptions\ProcurementException;
use App\Modules\Procurement\Domain\Enums\SupplierStatus;
use App\Modules\Procurement\Domain\Models\Supplier;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;

final readonly class ChangeSupplierStatus
{
    public function __construct(private TenantPermissionGuard $permissions) {}

    public function handle(TenantRequestContext $context, string $supplierId, SupplierStatus $status): Supplier
    {
        $this->permissions->authorizeManageCatalog($context);
        $supplier = Supplier::query()->where('tenant_id', $context->tenantId)->whereKey($supplierId)->first();

        if (! $supplier instanceof Supplier) {
            throw ProcurementException::supplierNotFound();
        }

        $supplier->forceFill(['status' => $status])->save();

        return $supplier->refresh();
    }
}
