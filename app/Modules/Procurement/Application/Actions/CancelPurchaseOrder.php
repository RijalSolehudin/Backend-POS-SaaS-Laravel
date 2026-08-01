<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Application\Actions;

use App\Modules\Procurement\Application\Exceptions\ProcurementException;
use App\Modules\Procurement\Domain\Enums\PurchaseOrderStatus;
use App\Modules\Procurement\Domain\Models\PurchaseOrder;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;

final readonly class CancelPurchaseOrder
{
    public function __construct(private TenantPermissionGuard $permissions) {}

    public function handle(TenantRequestContext $context, string $purchaseOrderId, string $reason): PurchaseOrder
    {
        $this->permissions->authorizeManageCatalog($context);
        $reason = trim($reason);

        if ($reason === '') {
            throw ProcurementException::reasonRequired();
        }

        $po = PurchaseOrder::query()->where('tenant_id', $context->tenantId)->whereKey($purchaseOrderId)->first();

        if (! $po instanceof PurchaseOrder) {
            throw ProcurementException::poNotFound();
        }

        if (in_array($po->status, [PurchaseOrderStatus::PartiallyReceived, PurchaseOrderStatus::Received, PurchaseOrderStatus::Cancelled], true)) {
            throw ProcurementException::poInvalidState();
        }

        $po->forceFill([
            'status' => PurchaseOrderStatus::Cancelled,
            'cancelled_by_user_id' => $context->userId,
            'cancel_reason' => $reason,
            'cancelled_at' => now(),
        ])->save();

        return $po->refresh();
    }
}
