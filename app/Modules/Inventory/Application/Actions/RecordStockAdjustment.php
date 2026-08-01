<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Actions;

use App\Modules\Inventory\Application\Data\StockAdjustmentInput;
use App\Modules\Inventory\Application\Data\StockMovementInput;
use App\Modules\Inventory\Application\Exceptions\InventoryException;
use App\Modules\Inventory\Application\Services\DecimalQuantity;
use App\Modules\Inventory\Application\Services\InventoryIdempotencyStore;
use App\Modules\Inventory\Domain\Enums\InventoryStatus;
use App\Modules\Inventory\Domain\Enums\StockMovementType;
use App\Modules\Inventory\Domain\Models\InventoryIdempotencyRecord;
use App\Modules\Inventory\Domain\Models\InventoryItem;
use App\Modules\Inventory\Domain\Models\InventoryStockMovement;
use App\Modules\Sales\Application\Actions\ConsumeSensitiveActionApproval;
use App\Modules\Tenancy\Application\Contracts\TenantCatalogReference;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use Illuminate\Support\Facades\DB;

final readonly class RecordStockAdjustment
{
    public function __construct(
        private TenantCatalogReference $tenancy,
        private TenantPermissionGuard $permissions,
        private InventoryIdempotencyStore $idempotency,
        private ConsumeSensitiveActionApproval $approvals,
        private RecordStockMovement $movements,
        private RecordInventoryAuditEvent $audit,
        private DecimalQuantity $quantity,
    ) {}

    public static function approvalFingerprint(
        string $outletId,
        string $itemId,
        StockMovementType $movementType,
        string $quantity,
        string $reason,
        string $idempotencyKey,
    ): string {
        return hash('sha256', json_encode([
            'action' => 'inventory.adjustments.record',
            'idempotency_key' => $idempotencyKey,
            'item_id' => $itemId,
            'movement_type' => $movementType->value,
            'outlet_id' => $outletId,
            'quantity' => trim($quantity),
            'reason' => trim($reason),
        ], JSON_THROW_ON_ERROR));
    }

    public function handle(TenantRequestContext $context, StockAdjustmentInput $input, string $idempotencyKey): InventoryStockMovement
    {
        $reason = trim($input->reason);

        if ($reason === '') {
            throw InventoryException::reasonRequired();
        }

        if (trim($idempotencyKey) === '') {
            throw InventoryException::idempotencyKeyRequired();
        }

        if (! in_array($input->movementType, [StockMovementType::AdjustmentIncrease, StockMovementType::AdjustmentDecrease], true)) {
            throw InventoryException::invalidAdjustmentType();
        }

        $quantity = $this->quantity->normalize($input->quantity);
        $requestHash = $this->requestHash($input, $quantity, $reason);

        return DB::transaction(function () use ($context, $input, $idempotencyKey, $reason, $quantity, $requestHash): InventoryStockMovement {
            $record = $this->idempotency->findForUpdate(
                tenantId: $context->tenantId,
                outletId: $input->outletId,
                userId: $context->userId,
                action: 'inventory.adjustments.record',
                idempotencyKey: $idempotencyKey,
            );

            if ($record instanceof InventoryIdempotencyRecord) {
                return $this->replay($record, $requestHash, $context->tenantId, $input->outletId);
            }

            $this->permissions->authorizeManageCatalog($context);
            $item = $this->activeItem($context, $input->itemId);
            $this->ensureTenantOutletAndCurrency($context, $input->outletId, $input->currency);
            $this->consumeApprovalIfRequired($context, $input, $quantity, $reason, $idempotencyKey);

            $signedQuantity = $input->movementType === StockMovementType::AdjustmentDecrease
                ? '-'.$quantity
                : $quantity;
            $unitCostMinor = $input->movementType === StockMovementType::AdjustmentIncrease
                ? $this->quantity->unitCostMinor($input->totalCostMinor ?? 0, $this->quantity->toScaled($quantity))
                : null;

            $movement = $this->movements->handle(new StockMovementInput(
                tenantId: $context->tenantId,
                outletId: $input->outletId,
                itemId: $input->itemId,
                unitId: $item->unit_id,
                actorUserId: $context->userId,
                movementType: $input->movementType,
                sourceType: 'stock_adjustment',
                sourceId: null,
                quantity: $signedQuantity,
                unitCostMinor: $unitCostMinor,
                totalCostMinor: $input->movementType === StockMovementType::AdjustmentIncrease ? ($input->totalCostMinor ?? 0) : null,
                currency: mb_strtoupper(trim($input->currency)),
                reason: $reason,
                idempotencyKey: $idempotencyKey,
            ));

            $this->idempotency->create(
                tenantId: $context->tenantId,
                outletId: $input->outletId,
                userId: $context->userId,
                action: 'inventory.adjustments.record',
                idempotencyKey: $idempotencyKey,
                requestHash: $requestHash,
                resourceType: 'inventory_stock_movement',
                resourceId: $movement->id,
                responseStatus: 201,
                responseBody: ['movement_id' => $movement->id],
            );

            $this->audit->handle(
                tenantId: $context->tenantId,
                outletId: $input->outletId,
                actorUserId: $context->userId,
                eventType: 'inventory_adjustment.recorded',
                targetType: 'inventory_stock_movement',
                targetId: $movement->id,
                outcome: 'recorded',
                reason: $reason,
                metadata: [
                    'approval_id' => $input->approvalId,
                    'item_id' => $input->itemId,
                    'movement_type' => $input->movementType->value,
                    'quantity' => $movement->quantity,
                ],
            );

            return $movement;
        });
    }

    private function replay(InventoryIdempotencyRecord $record, string $requestHash, string $tenantId, string $outletId): InventoryStockMovement
    {
        if ($record->request_hash !== $requestHash || $record->resource_id === null) {
            throw InventoryException::idempotencyConflict();
        }

        $movement = InventoryStockMovement::query()
            ->where('tenant_id', $tenantId)
            ->where('outlet_id', $outletId)
            ->whereKey($record->resource_id)
            ->first();

        if (! $movement instanceof InventoryStockMovement) {
            throw InventoryException::idempotencyConflict();
        }

        return $movement;
    }

    private function activeItem(TenantRequestContext $context, string $itemId): InventoryItem
    {
        $item = InventoryItem::query()
            ->where('tenant_id', $context->tenantId)
            ->whereKey($itemId)
            ->lockForUpdate()
            ->first();

        if (! $item instanceof InventoryItem) {
            throw InventoryException::itemNotFound();
        }

        if ($item->status !== InventoryStatus::Active) {
            throw InventoryException::itemInactive();
        }

        return $item;
    }

    private function ensureTenantOutletAndCurrency(TenantRequestContext $context, string $outletId, string $currency): void
    {
        $tenant = $this->tenancy->tenant($context->tenantId);

        if ($tenant === null || mb_strtoupper(trim($currency)) !== $tenant->currency) {
            throw InventoryException::currencyMismatch();
        }

        if (! $this->tenancy->activeOutletExists($context->tenantId, $outletId)) {
            throw InventoryException::outletNotFound();
        }
    }

    private function consumeApprovalIfRequired(
        TenantRequestContext $context,
        StockAdjustmentInput $input,
        string $quantity,
        string $reason,
        string $idempotencyKey,
    ): void {
        if ($input->movementType !== StockMovementType::AdjustmentDecrease) {
            return;
        }

        if (! $this->approvalRequired($quantity, 'inventory.approval.adjustment_decrease_quantity_threshold')) {
            return;
        }

        if ($input->approvalId === null || trim($input->approvalId) === '') {
            throw InventoryException::approvalRequired();
        }

        $this->approvals->handle(
            tenantId: $context->tenantId,
            outletId: $input->outletId,
            performerUserId: $context->userId,
            approvalId: $input->approvalId,
            action: 'inventory.adjustments.record',
            targetType: 'inventory_item',
            targetId: $input->itemId,
            requestFingerprint: self::approvalFingerprint($input->outletId, $input->itemId, $input->movementType, $quantity, $reason, $idempotencyKey),
        );
    }

    private function approvalRequired(string $quantity, string $configKey): bool
    {
        $threshold = config($configKey, '0.000');
        $threshold = is_string($threshold) ? $threshold : '0.000';

        return $this->quantity->toScaled($quantity) > $this->quantity->toScaled($threshold);
    }

    private function requestHash(StockAdjustmentInput $input, string $quantity, string $reason): string
    {
        return hash('sha256', json_encode([
            'approval_id' => $input->approvalId,
            'currency' => mb_strtoupper(trim($input->currency)),
            'item_id' => $input->itemId,
            'movement_type' => $input->movementType->value,
            'outlet_id' => $input->outletId,
            'quantity' => $quantity,
            'reason' => $reason,
            'total_cost_minor' => $input->totalCostMinor,
        ], JSON_THROW_ON_ERROR));
    }
}
