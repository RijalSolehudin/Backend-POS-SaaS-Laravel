<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Application\Actions;

use App\Modules\Inventory\Domain\Models\InventoryItem;
use App\Modules\Procurement\Application\Data\SupplierItemInput;
use App\Modules\Procurement\Application\Exceptions\ProcurementException;
use App\Modules\Procurement\Domain\Models\Supplier;
use App\Modules\Procurement\Domain\Models\SupplierItem;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;

final readonly class UpsertSupplierItem
{
    public function __construct(private TenantPermissionGuard $permissions) {}

    public function handle(TenantRequestContext $context, SupplierItemInput $input): SupplierItem
    {
        $this->permissions->authorizeManageCatalog($context);
        $supplier = Supplier::query()->where('tenant_id', $context->tenantId)->whereKey($input->supplierId)->first();
        $item = InventoryItem::query()->where('tenant_id', $context->tenantId)->whereKey($input->inventoryItemId)->first();

        if (! $supplier instanceof Supplier) {
            throw ProcurementException::supplierNotFound();
        }

        if (! $item instanceof InventoryItem) {
            throw ProcurementException::crossTenantReference();
        }

        return SupplierItem::query()->updateOrCreate(
            [
                'tenant_id' => $context->tenantId,
                'supplier_id' => $supplier->id,
                'inventory_item_id' => $item->id,
            ],
            [
                'supplier_sku' => mb_strtoupper(trim($input->supplierSku)),
                'last_price_minor' => $input->lastPriceMinor,
                'currency' => mb_strtoupper(trim($input->currency)),
                'is_active' => $input->isActive,
            ],
        );
    }
}
