<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Actions;

use App\Modules\Inventory\Application\Data\StockMovementInput;
use App\Modules\Inventory\Application\Exceptions\InventoryException;
use App\Modules\Inventory\Application\Services\InventoryIdempotencyStore;
use App\Modules\Inventory\Domain\Enums\StockMovementType;
use App\Modules\Inventory\Domain\Enums\TransferStatus;
use App\Modules\Inventory\Domain\Models\InventoryIdempotencyRecord;
use App\Modules\Inventory\Domain\Models\InventoryStockMovement;
use App\Modules\Inventory\Domain\Models\InventoryTransfer;
use App\Modules\Inventory\Domain\Models\InventoryTransferLine;
use App\Modules\Sales\Application\Actions\ConsumeSensitiveActionApproval;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use Illuminate\Support\Facades\DB;

final readonly class DispatchInventoryTransfer
{
    public function __construct(
        private TenantPermissionGuard $permissions,
        private InventoryIdempotencyStore $idempotency,
        private ConsumeSensitiveActionApproval $approvals,
        private RecordStockMovement $movements,
        private RecordInventoryAuditEvent $audit,
    ) {}

    public function handle(TenantRequestContext $context, string $transferId, string $idempotencyKey): InventoryTransfer
    {
        if (trim($idempotencyKey) === '') {
            throw InventoryException::idempotencyKeyRequired();
        }

        $requestHash = hash('sha256', $transferId);

        return DB::transaction(function () use ($context, $transferId, $idempotencyKey, $requestHash): InventoryTransfer {
            $transfer = $this->transferForUpdate($context, $transferId);
            $record = $this->idempotency->findForUpdate(
                tenantId: $context->tenantId,
                outletId: $transfer->source_outlet_id,
                userId: $context->userId,
                action: 'inventory.transfers.dispatch',
                idempotencyKey: $idempotencyKey,
            );

            if ($record instanceof InventoryIdempotencyRecord) {
                if ($record->request_hash !== $requestHash || $record->resource_id !== $transferId) {
                    throw InventoryException::idempotencyConflict();
                }

                return $transfer;
            }

            $this->permissions->authorizeManageCatalog($context);

            if ($transfer->status !== TransferStatus::Approved || $transfer->approval_id === null) {
                throw InventoryException::transferInvalidState();
            }

            $this->approvals->handle(
                tenantId: $context->tenantId,
                outletId: $transfer->source_outlet_id,
                performerUserId: $context->userId,
                approvalId: $transfer->approval_id,
                action: 'inventory.transfers.dispatch',
                targetType: 'inventory_transfer',
                targetId: $transfer->id,
                requestFingerprint: $this->fingerprint($transfer),
            );

            foreach ($this->lines($transfer) as $line) {
                $this->movements->handle(new StockMovementInput(
                    tenantId: $context->tenantId,
                    outletId: $transfer->source_outlet_id,
                    itemId: $line->item_id,
                    unitId: $line->unit_id,
                    actorUserId: $context->userId,
                    movementType: StockMovementType::TransferOut,
                    sourceType: 'inventory_transfer',
                    sourceId: $transfer->id,
                    quantity: '-'.(string) $line->quantity,
                    unitCostMinor: null,
                    totalCostMinor: null,
                    currency: $this->balanceCurrency($context->tenantId, $transfer->source_outlet_id, $line->item_id),
                    reason: $transfer->reason,
                    idempotencyKey: $idempotencyKey,
                ));
            }

            $transfer->forceFill([
                'status' => TransferStatus::Dispatched,
                'dispatched_by_user_id' => $context->userId,
                'dispatched_at' => now(),
            ])->save();

            $this->idempotency->create(
                tenantId: $context->tenantId,
                outletId: $transfer->source_outlet_id,
                userId: $context->userId,
                action: 'inventory.transfers.dispatch',
                idempotencyKey: $idempotencyKey,
                requestHash: $requestHash,
                resourceType: 'inventory_transfer',
                resourceId: $transfer->id,
                responseStatus: 200,
                responseBody: ['transfer_id' => $transfer->id],
            );

            $this->audit->handle(
                tenantId: $context->tenantId,
                outletId: $transfer->source_outlet_id,
                actorUserId: $context->userId,
                eventType: 'inventory_transfer.dispatched',
                targetType: 'inventory_transfer',
                targetId: $transfer->id,
                outcome: 'dispatched',
                reason: $transfer->reason,
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

    /**
     * @return list<InventoryTransferLine>
     */
    private function lines(InventoryTransfer $transfer): array
    {
        return array_values(InventoryTransferLine::query()
            ->where('tenant_id', $transfer->tenant_id)
            ->where('transfer_id', $transfer->id)
            ->orderBy('item_id')
            ->get()
            ->all());
    }

    private function fingerprint(InventoryTransfer $transfer): string
    {
        return hash('sha256', json_encode([
            'action' => 'inventory.transfers.dispatch',
            'destination_outlet_id' => $transfer->destination_outlet_id,
            'lines' => array_map(
                fn (InventoryTransferLine $line): array => ['item_id' => $line->item_id, 'quantity' => (string) $line->quantity],
                $this->lines($transfer),
            ),
            'reason' => $transfer->reason,
            'source_outlet_id' => $transfer->source_outlet_id,
            'transfer_id' => $transfer->id,
        ], JSON_THROW_ON_ERROR));
    }

    private function balanceCurrency(string $tenantId, string $outletId, string $itemId): string
    {
        $movement = InventoryStockMovement::query()
            ->where('tenant_id', $tenantId)
            ->where('outlet_id', $outletId)
            ->where('item_id', $itemId)
            ->latest('occurred_at')
            ->first();

        return $movement instanceof InventoryStockMovement ? $movement->currency : 'IDR';
    }
}
