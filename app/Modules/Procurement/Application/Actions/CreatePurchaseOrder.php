<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Application\Actions;

use App\Modules\Inventory\Application\Services\DecimalQuantity;
use App\Modules\Procurement\Application\Data\PurchaseOrderInput;
use App\Modules\Procurement\Application\Exceptions\ProcurementException;
use App\Modules\Procurement\Domain\Enums\PurchaseOrderStatus;
use App\Modules\Procurement\Domain\Enums\SupplierStatus;
use App\Modules\Procurement\Domain\Models\PurchaseOrder;
use App\Modules\Procurement\Domain\Models\PurchaseOrderLine;
use App\Modules\Procurement\Domain\Models\Supplier;
use App\Modules\Procurement\Domain\Models\SupplierItem;
use App\Modules\Tenancy\Application\Contracts\TenantCatalogReference;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use Illuminate\Support\Facades\DB;

final readonly class CreatePurchaseOrder
{
    public function __construct(
        private TenantCatalogReference $tenancy,
        private TenantPermissionGuard $permissions,
        private DecimalQuantity $quantity,
    ) {}

    public function handle(TenantRequestContext $context, PurchaseOrderInput $input): PurchaseOrder
    {
        $this->permissions->authorizeManageCatalog($context);
        $this->ensureOutlet($context, $input->outletId);

        if ($input->lines === []) {
            throw ProcurementException::supplierItemNotFound();
        }

        return DB::transaction(function () use ($context, $input): PurchaseOrder {
            $supplier = Supplier::query()->where('tenant_id', $context->tenantId)->whereKey($input->supplierId)->first();

            if (! $supplier instanceof Supplier || $supplier->status !== SupplierStatus::Active) {
                throw ProcurementException::supplierNotFound();
            }

            $po = PurchaseOrder::query()->create([
                'tenant_id' => $context->tenantId,
                'outlet_id' => $input->outletId,
                'supplier_id' => $supplier->id,
                'po_number' => $this->poNumber(),
                'status' => PurchaseOrderStatus::Draft,
                'total_minor' => 0,
                'currency' => mb_strtoupper(trim($input->currency)),
                'notes' => $input->notes === null ? null : trim($input->notes),
                'created_by_user_id' => $context->userId,
            ]);
            $total = 0;

            foreach ($input->lines as $line) {
                $supplierItem = SupplierItem::query()
                    ->where('tenant_id', $context->tenantId)
                    ->where('supplier_id', $supplier->id)
                    ->whereKey($line->supplierItemId)
                    ->first();

                if (! $supplierItem instanceof SupplierItem || ! $supplierItem->is_active) {
                    throw ProcurementException::supplierItemNotFound();
                }

                $quantity = $this->quantity->normalize($line->quantity);
                $scaledQuantity = $this->quantity->toScaled($quantity);

                if ($scaledQuantity <= 0) {
                    throw ProcurementException::receiptOverReceived();
                }

                $lineTotal = intdiv(($scaledQuantity * $line->unitPriceMinor) + 500, 1000);
                $total += $lineTotal;

                PurchaseOrderLine::query()->create([
                    'tenant_id' => $context->tenantId,
                    'purchase_order_id' => $po->id,
                    'supplier_item_id' => $supplierItem->id,
                    'inventory_item_id' => $supplierItem->inventory_item_id,
                    'unit_id' => $this->inventoryUnitId($supplierItem->inventory_item_id),
                    'quantity' => $quantity,
                    'received_quantity' => '0.000',
                    'unit_price_minor' => $line->unitPriceMinor,
                    'line_total_minor' => $lineTotal,
                ]);
            }

            $po->forceFill(['total_minor' => $total])->save();

            return $po->refresh();
        });
    }

    private function ensureOutlet(TenantRequestContext $context, string $outletId): void
    {
        if (! $this->tenancy->activeOutletExists($context->tenantId, $outletId)) {
            throw ProcurementException::crossTenantReference();
        }
    }

    private function inventoryUnitId(string $inventoryItemId): string
    {
        $unitId = (string) DB::table('inventory_items')->where('id', $inventoryItemId)->value('unit_id');

        if ($unitId === '') {
            throw ProcurementException::crossTenantReference();
        }

        return $unitId;
    }

    private function poNumber(): string
    {
        return 'PO-'.now()->format('YmdHis').'-'.strtolower(str()->random(6));
    }
}
