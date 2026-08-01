<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Application\Actions;

use App\Modules\Procurement\Application\Data\SupplierInput;
use App\Modules\Procurement\Application\Exceptions\ProcurementException;
use App\Modules\Procurement\Domain\Models\Supplier;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;

final readonly class UpdateSupplier
{
    public function __construct(private TenantPermissionGuard $permissions) {}

    public function handle(TenantRequestContext $context, string $supplierId, SupplierInput $input): Supplier
    {
        $this->permissions->authorizeManageCatalog($context);
        $supplier = Supplier::query()->where('tenant_id', $context->tenantId)->whereKey($supplierId)->first();

        if (! $supplier instanceof Supplier) {
            throw ProcurementException::supplierNotFound();
        }

        $code = mb_strtoupper(trim($input->code));
        $exists = Supplier::query()
            ->where('tenant_id', $context->tenantId)
            ->where('code', $code)
            ->whereKeyNot($supplier->id)
            ->exists();

        if ($exists) {
            throw ProcurementException::supplierCodeUnavailable();
        }

        $supplier->forceFill([
            'name' => trim($input->name),
            'code' => $code,
        ])->save();

        return $supplier->refresh();
    }
}
