<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Actions;

use App\Modules\Inventory\Application\Exceptions\InventoryException;
use App\Modules\Inventory\Domain\Enums\TransferStatus;
use App\Modules\Inventory\Domain\Models\InventoryTransfer;
use App\Modules\Inventory\Domain\Models\InventoryTransferLine;
use App\Modules\Sales\Application\Actions\RequestSensitiveActionApproval;
use App\Modules\Sales\Domain\Models\SensitiveActionApproval;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use Illuminate\Support\Facades\DB;

final readonly class RequestInventoryTransferApproval
{
    public function __construct(
        private TenantPermissionGuard $permissions,
        private RequestSensitiveActionApproval $approvals,
        private RecordInventoryAuditEvent $audit,
    ) {}

    public function handle(TenantRequestContext $context, string $transferId, string $idempotencyKey): SensitiveActionApproval
    {
        if (trim($idempotencyKey) === '') {
            throw InventoryException::idempotencyKeyRequired();
        }

        return DB::transaction(function () use ($context, $transferId, $idempotencyKey): SensitiveActionApproval {
            $this->permissions->authorizeManageCatalog($context);
            $transfer = $this->transferForUpdate($context, $transferId);

            if (! in_array($transfer->status, [TransferStatus::Draft, TransferStatus::Requested], true)) {
                throw InventoryException::transferInvalidState();
            }

            $approval = $this->approvals->handle(
                tenantId: $context->tenantId,
                outletId: $transfer->source_outlet_id,
                performerUserId: $context->userId,
                action: 'inventory.transfers.dispatch',
                targetType: 'inventory_transfer',
                targetId: $transfer->id,
                requestFingerprint: $this->fingerprint($transfer),
                reason: $transfer->reason,
                idempotencyKey: $idempotencyKey,
            );

            $transfer->forceFill([
                'status' => TransferStatus::Requested,
                'approval_id' => $approval->id,
                'requested_at' => $transfer->requested_at ?? now(),
            ])->save();

            $this->audit->handle(
                tenantId: $context->tenantId,
                outletId: $transfer->source_outlet_id,
                actorUserId: $context->userId,
                eventType: 'inventory_transfer.approval_requested',
                targetType: 'inventory_transfer',
                targetId: $transfer->id,
                outcome: 'requested',
                reason: $transfer->reason,
                metadata: ['approval_id' => $approval->id],
            );

            return $approval;
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

    private function fingerprint(InventoryTransfer $transfer): string
    {
        return hash('sha256', json_encode([
            'action' => 'inventory.transfers.dispatch',
            'destination_outlet_id' => $transfer->destination_outlet_id,
            'lines' => array_map(
                fn (InventoryTransferLine $line): array => ['item_id' => $line->item_id, 'quantity' => (string) $line->quantity],
                array_values(InventoryTransferLine::query()
                    ->where('tenant_id', $transfer->tenant_id)
                    ->where('transfer_id', $transfer->id)
                    ->orderBy('item_id')
                    ->get()
                    ->all()),
            ),
            'reason' => $transfer->reason,
            'source_outlet_id' => $transfer->source_outlet_id,
            'transfer_id' => $transfer->id,
        ], JSON_THROW_ON_ERROR));
    }
}
