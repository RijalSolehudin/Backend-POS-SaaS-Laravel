<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Application\Actions;

use App\Modules\Procurement\Application\Exceptions\ProcurementException;
use App\Modules\Procurement\Application\Services\ProcurementIdempotencyStore;
use App\Modules\Procurement\Domain\Enums\PurchaseOrderStatus;
use App\Modules\Procurement\Domain\Models\ProcurementIdempotencyRecord;
use App\Modules\Procurement\Domain\Models\PurchaseOrder;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use Illuminate\Support\Facades\DB;

final readonly class ApprovePurchaseOrder
{
    public function __construct(
        private TenantPermissionGuard $permissions,
        private ProcurementIdempotencyStore $idempotency,
    ) {}

    public function handle(TenantRequestContext $context, string $purchaseOrderId, string $idempotencyKey): PurchaseOrder
    {
        if (trim($idempotencyKey) === '') {
            throw ProcurementException::idempotencyKeyRequired();
        }

        $this->permissions->authorizeManageCatalog($context);
        $requestHash = hash('sha256', $purchaseOrderId);

        return DB::transaction(function () use ($context, $purchaseOrderId, $idempotencyKey, $requestHash): PurchaseOrder {
            $po = $this->poForUpdate($context, $purchaseOrderId);
            $record = $this->idempotency->findForUpdate($context->tenantId, $po->outlet_id, $context->userId, 'procurement.po.approve', $idempotencyKey);

            if ($record instanceof ProcurementIdempotencyRecord) {
                if ($record->request_hash !== $requestHash || $record->resource_id !== $po->id) {
                    throw ProcurementException::idempotencyConflict();
                }

                return $po;
            }

            if ($po->status !== PurchaseOrderStatus::Submitted) {
                throw ProcurementException::poInvalidState();
            }

            $po->forceFill([
                'status' => PurchaseOrderStatus::Approved,
                'approved_by_user_id' => $context->userId,
                'approved_at' => now(),
            ])->save();
            $this->idempotency->create($context->tenantId, $po->outlet_id, $context->userId, 'procurement.po.approve', $idempotencyKey, $requestHash, 'purchase_order', $po->id);

            return $po->refresh();
        });
    }

    private function poForUpdate(TenantRequestContext $context, string $purchaseOrderId): PurchaseOrder
    {
        $po = PurchaseOrder::query()->where('tenant_id', $context->tenantId)->whereKey($purchaseOrderId)->lockForUpdate()->first();

        if (! $po instanceof PurchaseOrder) {
            throw ProcurementException::poNotFound();
        }

        return $po;
    }
}
