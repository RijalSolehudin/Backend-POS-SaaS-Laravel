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
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use Illuminate\Support\Facades\DB;

final readonly class ReceiveInventoryTransfer
{
    public function __construct(
        private TenantPermissionGuard $permissions,
        private InventoryIdempotencyStore $idempotency,
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
                outletId: $transfer->destination_outlet_id,
                userId: $context->userId,
                action: 'inventory.transfers.receive',
                idempotencyKey: $idempotencyKey,
            );

            if ($record instanceof InventoryIdempotencyRecord) {
                if ($record->request_hash !== $requestHash || $record->resource_id !== $transferId) {
                    throw InventoryException::idempotencyConflict();
                }

                return $transfer;
            }

            $this->permissions->authorizeManageCatalog($context);

            if ($transfer->status !== TransferStatus::Dispatched) {
                throw InventoryException::transferInvalidState();
            }

            foreach ($this->lines($transfer) as $line) {
                $this->movements->handle(new StockMovementInput(
                    tenantId: $context->tenantId,
                    outletId: $transfer->destination_outlet_id,
                    itemId: $line->item_id,
                    unitId: $line->unit_id,
                    actorUserId: $context->userId,
                    movementType: StockMovementType::TransferIn,
                    sourceType: 'inventory_transfer',
                    sourceId: $transfer->id,
                    quantity: (string) $line->quantity,
                    unitCostMinor: null,
                    totalCostMinor: $this->transferOutCost($transfer, $line),
                    currency: $this->transferOutCurrency($transfer, $line),
                    reason: $transfer->reason,
                    idempotencyKey: $idempotencyKey,
                ));
            }

            $transfer->forceFill([
                'status' => TransferStatus::Received,
                'received_by_user_id' => $context->userId,
                'received_at' => now(),
            ])->save();

            $this->idempotency->create(
                tenantId: $context->tenantId,
                outletId: $transfer->destination_outlet_id,
                userId: $context->userId,
                action: 'inventory.transfers.receive',
                idempotencyKey: $idempotencyKey,
                requestHash: $requestHash,
                resourceType: 'inventory_transfer',
                resourceId: $transfer->id,
                responseStatus: 200,
                responseBody: ['transfer_id' => $transfer->id],
            );

            $this->audit->handle(
                tenantId: $context->tenantId,
                outletId: $transfer->destination_outlet_id,
                actorUserId: $context->userId,
                eventType: 'inventory_transfer.received',
                targetType: 'inventory_transfer',
                targetId: $transfer->id,
                outcome: 'received',
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

    private function transferOutCost(InventoryTransfer $transfer, InventoryTransferLine $line): int
    {
        $movement = $this->transferOutMovement($transfer, $line);

        return abs($movement->total_cost_minor);
    }

    private function transferOutCurrency(InventoryTransfer $transfer, InventoryTransferLine $line): string
    {
        return $this->transferOutMovement($transfer, $line)->currency;
    }

    private function transferOutMovement(InventoryTransfer $transfer, InventoryTransferLine $line): InventoryStockMovement
    {
        $movement = InventoryStockMovement::query()
            ->where('tenant_id', $transfer->tenant_id)
            ->where('outlet_id', $transfer->source_outlet_id)
            ->where('item_id', $line->item_id)
            ->where('source_type', 'inventory_transfer')
            ->where('source_id', $transfer->id)
            ->where('movement_type', StockMovementType::TransferOut)
            ->first();

        if (! $movement instanceof InventoryStockMovement) {
            throw InventoryException::transferInvalidState();
        }

        return $movement;
    }
}
