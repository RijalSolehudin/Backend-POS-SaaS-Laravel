<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Actions;

use App\Modules\Inventory\Application\Data\OpeningBalanceInput;
use App\Modules\Inventory\Application\Data\StockMovementInput;
use App\Modules\Inventory\Application\Exceptions\InventoryException;
use App\Modules\Inventory\Application\Services\DecimalQuantity;
use App\Modules\Inventory\Application\Services\InventoryIdempotencyStore;
use App\Modules\Inventory\Domain\Enums\InventoryStatus;
use App\Modules\Inventory\Domain\Enums\StockMovementType;
use App\Modules\Inventory\Domain\Models\InventoryIdempotencyRecord;
use App\Modules\Inventory\Domain\Models\InventoryItem;
use App\Modules\Inventory\Domain\Models\InventoryStockMovement;
use App\Modules\Tenancy\Application\Contracts\TenantCatalogReference;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use Illuminate\Support\Facades\DB;

final readonly class RecordOpeningBalance
{
    public function __construct(
        private TenantCatalogReference $tenancy,
        private TenantPermissionGuard $permissions,
        private InventoryIdempotencyStore $idempotency,
        private RecordStockMovement $movements,
        private RecordInventoryAuditEvent $audit,
        private DecimalQuantity $quantity,
    ) {}

    public function handle(TenantRequestContext $context, OpeningBalanceInput $input, string $idempotencyKey): InventoryStockMovement
    {
        if (trim($idempotencyKey) === '') {
            throw InventoryException::idempotencyKeyRequired();
        }

        $requestHash = $this->requestHash($input);

        return DB::transaction(function () use ($context, $input, $idempotencyKey, $requestHash): InventoryStockMovement {
            $record = $this->idempotency->findForUpdate(
                tenantId: $context->tenantId,
                outletId: $input->outletId,
                userId: $context->userId,
                action: 'inventory.opening_balance.record',
                idempotencyKey: $idempotencyKey,
            );

            if ($record instanceof InventoryIdempotencyRecord) {
                if ($record->request_hash !== $requestHash || $record->resource_id === null) {
                    throw InventoryException::idempotencyConflict();
                }

                $movement = $this->movement($context->tenantId, $input->outletId, $record->resource_id);

                if (! $movement instanceof InventoryStockMovement) {
                    throw InventoryException::idempotencyConflict();
                }

                return $movement;
            }

            $this->permissions->authorizeManageCatalog($context);

            $tenant = $this->tenancy->tenant($context->tenantId);

            if ($tenant === null || mb_strtoupper(trim($input->currency)) !== $tenant->currency) {
                throw InventoryException::currencyMismatch();
            }

            if (! $this->tenancy->activeOutletExists($context->tenantId, $input->outletId)) {
                throw InventoryException::outletNotFound();
            }

            $item = InventoryItem::query()
                ->where('tenant_id', $context->tenantId)
                ->whereKey($input->itemId)
                ->lockForUpdate()
                ->first();

            if (! $item instanceof InventoryItem) {
                throw InventoryException::itemNotFound();
            }

            if ($item->status !== InventoryStatus::Active) {
                throw InventoryException::itemInactive();
            }

            if ($this->openingBalanceExists($context->tenantId, $input->outletId, $input->itemId)) {
                throw InventoryException::openingBalanceAlreadyRecorded();
            }

            $quantity = $this->quantity->normalize($input->quantity);
            $quantityScaled = $this->quantity->toScaled($quantity);
            $unitCostMinor = $this->quantity->unitCostMinor($input->totalCostMinor, $quantityScaled);

            $movement = $this->movements->handle(new StockMovementInput(
                tenantId: $context->tenantId,
                outletId: $input->outletId,
                itemId: $input->itemId,
                unitId: $item->unit_id,
                actorUserId: $context->userId,
                movementType: StockMovementType::OpeningBalance,
                sourceType: 'opening_balance',
                sourceId: null,
                quantity: $quantity,
                unitCostMinor: $unitCostMinor,
                totalCostMinor: $input->totalCostMinor,
                currency: $tenant->currency,
                reason: $input->reason,
                idempotencyKey: $idempotencyKey,
            ));

            $this->idempotency->create(
                tenantId: $context->tenantId,
                outletId: $input->outletId,
                userId: $context->userId,
                action: 'inventory.opening_balance.record',
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
                eventType: 'inventory_opening_balance.recorded',
                targetType: 'inventory_item',
                targetId: $input->itemId,
                outcome: 'success',
                metadata: [
                    'idempotency_key' => $idempotencyKey,
                    'movement_id' => $movement->id,
                    'quantity' => $movement->quantity,
                    'total_cost_minor' => $movement->total_cost_minor,
                ],
            );

            return $movement;
        });
    }

    private function openingBalanceExists(string $tenantId, string $outletId, string $itemId): bool
    {
        return InventoryStockMovement::query()
            ->where('tenant_id', $tenantId)
            ->where('outlet_id', $outletId)
            ->where('item_id', $itemId)
            ->where('movement_type', StockMovementType::OpeningBalance)
            ->exists();
    }

    private function movement(string $tenantId, string $outletId, string $movementId): ?InventoryStockMovement
    {
        return InventoryStockMovement::query()
            ->where('tenant_id', $tenantId)
            ->where('outlet_id', $outletId)
            ->whereKey($movementId)
            ->first();
    }

    private function requestHash(OpeningBalanceInput $input): string
    {
        $payload = [
            'currency' => mb_strtoupper(trim($input->currency)),
            'item_id' => $input->itemId,
            'outlet_id' => $input->outletId,
            'quantity' => $this->quantity->normalize($input->quantity),
            'reason' => $input->reason,
            'total_cost_minor' => $input->totalCostMinor,
        ];

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }
}
