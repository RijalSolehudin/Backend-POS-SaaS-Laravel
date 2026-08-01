<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Actions;

use App\Modules\Inventory\Application\Data\InventoryTransferInput;
use App\Modules\Inventory\Application\Exceptions\InventoryException;
use App\Modules\Inventory\Application\Services\DecimalQuantity;
use App\Modules\Inventory\Domain\Enums\InventoryStatus;
use App\Modules\Inventory\Domain\Enums\TransferStatus;
use App\Modules\Inventory\Domain\Models\InventoryItem;
use App\Modules\Inventory\Domain\Models\InventoryTransfer;
use App\Modules\Inventory\Domain\Models\InventoryTransferLine;
use App\Modules\Tenancy\Application\Contracts\TenantCatalogReference;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use Illuminate\Support\Facades\DB;

final readonly class CreateInventoryTransfer
{
    public function __construct(
        private TenantCatalogReference $tenancy,
        private TenantPermissionGuard $permissions,
        private RecordInventoryAuditEvent $audit,
        private DecimalQuantity $quantity,
    ) {}

    public function handle(TenantRequestContext $context, InventoryTransferInput $input): InventoryTransfer
    {
        $reason = trim($input->reason);

        if ($reason === '') {
            throw InventoryException::reasonRequired();
        }

        if ($input->sourceOutletId === $input->destinationOutletId) {
            throw InventoryException::transferSameOutlet();
        }

        if ($input->lines === []) {
            throw InventoryException::itemNotFound();
        }

        $this->permissions->authorizeManageCatalog($context);
        $this->ensureOutlet($context, $input->sourceOutletId);
        $this->ensureOutlet($context, $input->destinationOutletId);

        return DB::transaction(function () use ($context, $input, $reason): InventoryTransfer {
            $transfer = InventoryTransfer::query()->create([
                'tenant_id' => $context->tenantId,
                'source_outlet_id' => $input->sourceOutletId,
                'destination_outlet_id' => $input->destinationOutletId,
                'requested_by_user_id' => $context->userId,
                'status' => TransferStatus::Draft,
                'reason' => $reason,
            ]);

            foreach ($input->lines as $line) {
                $item = $this->activeItem($context, $line->itemId);
                $quantity = $this->quantity->normalize($line->quantity);

                if ($this->quantity->toScaled($quantity) <= 0) {
                    throw InventoryException::insufficientStock();
                }

                InventoryTransferLine::query()->create([
                    'tenant_id' => $context->tenantId,
                    'transfer_id' => $transfer->id,
                    'item_id' => $item->id,
                    'unit_id' => $item->unit_id,
                    'quantity' => $quantity,
                ]);
            }

            $this->audit->handle(
                tenantId: $context->tenantId,
                outletId: $input->sourceOutletId,
                actorUserId: $context->userId,
                eventType: 'inventory_transfer.created',
                targetType: 'inventory_transfer',
                targetId: $transfer->id,
                outcome: 'draft',
                reason: $reason,
                metadata: [
                    'destination_outlet_id' => $input->destinationOutletId,
                    'line_count' => count($input->lines),
                ],
            );

            return $transfer;
        });
    }

    private function ensureOutlet(TenantRequestContext $context, string $outletId): void
    {
        if (! $this->tenancy->activeOutletExists($context->tenantId, $outletId)) {
            throw InventoryException::outletNotFound();
        }
    }

    private function activeItem(TenantRequestContext $context, string $itemId): InventoryItem
    {
        $item = InventoryItem::query()
            ->where('tenant_id', $context->tenantId)
            ->whereKey($itemId)
            ->first();

        if (! $item instanceof InventoryItem) {
            throw InventoryException::itemNotFound();
        }

        if ($item->status !== InventoryStatus::Active) {
            throw InventoryException::itemInactive();
        }

        return $item;
    }
}
