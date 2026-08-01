<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Application\Actions;

use App\Modules\Procurement\Application\Exceptions\ProcurementException;
use App\Modules\Procurement\Domain\Enums\PurchaseOrderStatus;
use App\Modules\Procurement\Domain\Models\PurchaseOrder;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;

final readonly class MarkPurchaseOrderOrdered
{
    public function __construct(private TenantPermissionGuard $permissions) {}

    public function handle(TenantRequestContext $context, string $purchaseOrderId): PurchaseOrder
    {
        $this->permissions->authorizeManageCatalog($context);
        $po = PurchaseOrder::query()->where('tenant_id', $context->tenantId)->whereKey($purchaseOrderId)->first();

        if (! $po instanceof PurchaseOrder) {
            throw ProcurementException::poNotFound();
        }

        if ($po->status !== PurchaseOrderStatus::Approved) {
            throw ProcurementException::poApprovalRequired();
        }

        $po->forceFill([
            'status' => PurchaseOrderStatus::Ordered,
            'ordered_at' => now(),
        ])->save();

        return $po->refresh();
    }
}
