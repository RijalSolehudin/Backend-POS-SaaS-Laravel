<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Application\Actions;

use App\Modules\Procurement\Application\Data\SupplierInput;
use App\Modules\Procurement\Application\Exceptions\ProcurementException;
use App\Modules\Procurement\Domain\Enums\SupplierStatus;
use App\Modules\Procurement\Domain\Models\Supplier;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;

final readonly class CreateSupplier
{
    public function __construct(private TenantPermissionGuard $permissions) {}

    public function handle(TenantRequestContext $context, SupplierInput $input): Supplier
    {
        $this->permissions->authorizeManageCatalog($context);
        $code = $this->normalizeCode($input->code);

        if (Supplier::query()->where('tenant_id', $context->tenantId)->where('code', $code)->exists()) {
            throw ProcurementException::supplierCodeUnavailable();
        }

        return Supplier::query()->create([
            'tenant_id' => $context->tenantId,
            'name' => trim($input->name),
            'code' => $code,
            'status' => SupplierStatus::Active,
        ]);
    }

    private function normalizeCode(string $code): string
    {
        return mb_strtoupper(trim($code));
    }
}
