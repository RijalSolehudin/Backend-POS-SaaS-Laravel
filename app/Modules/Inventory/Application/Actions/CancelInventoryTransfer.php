<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Actions;

use App\Modules\Inventory\Application\Exceptions\InventoryException;
use App\Modules\Inventory\Domain\Enums\TransferStatus;
use App\Modules\Inventory\Domain\Models\InventoryTransfer;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use Illuminate\Support\Facades\DB;

final readonly class CancelInventoryTransfer
{
    public function __construct(
        private TenantPermissionGuard $permissions,
        private RecordInventoryAuditEvent $audit,
    ) {}

    public function handle(TenantRequestContext $context, string $transferId, string $reason): InventoryTransfer
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw InventoryException::reasonRequired();
        }

        return DB::transaction(function () use ($context, $transferId, $reason): InventoryTransfer {
            $this->permissions->authorizeManageCatalog($context);
            $transfer = $this->transferForUpdate($context, $transferId);

            if (! in_array($transfer->status, [TransferStatus::Draft, TransferStatus::Requested, TransferStatus::Approved], true)) {
                throw InventoryException::transferInvalidState();
            }

            $transfer->forceFill([
                'status' => TransferStatus::Cancelled,
                'cancelled_by_user_id' => $context->userId,
                'cancelled_at' => now(),
            ])->save();

            $this->audit->handle(
                tenantId: $context->tenantId,
                outletId: $transfer->source_outlet_id,
                actorUserId: $context->userId,
                eventType: 'inventory_transfer.cancelled',
                targetType: 'inventory_transfer',
                targetId: $transfer->id,
                outcome: 'cancelled',
                reason: $reason,
            );

            return $transfer->refresh();
        });
    }

    private function transferForUpdate(TenantRequestContext $context, string $transferId): InventoryTransfer
    {
        $transfer = InventoryTransfer::query()
            ->where('tenant_id', $context->tenantId)
            ->whereKey($transferId)
            ->lockForUpdate()
            ->first();

        if (! $transfer instanceof InventoryTransfer) {
            throw InventoryException::transferNotFound();
        }

        return $transfer;
    }
}
