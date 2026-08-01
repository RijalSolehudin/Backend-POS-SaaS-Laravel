<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Actions;

use App\Modules\Inventory\Application\Exceptions\InventoryException;
use App\Modules\Inventory\Domain\Enums\TransferStatus;
use App\Modules\Inventory\Domain\Models\InventoryTransfer;
use App\Modules\Sales\Application\Actions\ApproveSensitiveActionApproval;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use Illuminate\Support\Facades\DB;

final readonly class ApproveInventoryTransfer
{
    public function __construct(
        private ApproveSensitiveActionApproval $approvals,
        private RecordInventoryAuditEvent $audit,
    ) {}

    public function handle(TenantRequestContext $context, string $transferId, string $approverUserId, string $reason): InventoryTransfer
    {
        return DB::transaction(function () use ($context, $transferId, $approverUserId, $reason): InventoryTransfer {
            $transfer = $this->transferForUpdate($context, $transferId);

            if ($transfer->status !== TransferStatus::Requested || $transfer->approval_id === null) {
                throw InventoryException::transferInvalidState();
            }

            $this->approvals->approve(
                tenantId: $context->tenantId,
                approvalId: $transfer->approval_id,
                approverUserId: $approverUserId,
                reason: $reason,
            );

            $transfer->forceFill([
                'status' => TransferStatus::Approved,
                'approved_at' => now(),
            ])->save();

            $this->audit->handle(
                tenantId: $context->tenantId,
                outletId: $transfer->source_outlet_id,
                actorUserId: $approverUserId,
                eventType: 'inventory_transfer.approved',
                targetType: 'inventory_transfer',
                targetId: $transfer->id,
                outcome: 'approved',
                reason: $reason,
                metadata: ['approval_id' => $transfer->approval_id],
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
