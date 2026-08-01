<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Actions;

use App\Modules\Inventory\Application\Exceptions\InventoryException;
use App\Modules\Inventory\Domain\Enums\InventoryStatus;
use App\Modules\Inventory\Domain\Models\InventoryItem;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;

final readonly class ChangeInventoryItemStatus
{
    public function __construct(
        private TenantPermissionGuard $permissions,
        private RecordInventoryAuditEvent $audit,
    ) {}

    public function handle(TenantRequestContext $context, string $itemId, InventoryStatus $status): InventoryItem
    {
        $this->permissions->authorizeManageCatalog($context);

        $item = InventoryItem::query()
            ->where('tenant_id', $context->tenantId)
            ->whereKey($itemId)
            ->first();

        if (! $item instanceof InventoryItem) {
            throw InventoryException::itemNotFound();
        }

        $item->forceFill(['status' => $status])->save();

        $this->audit->handle(
            tenantId: $context->tenantId,
            outletId: null,
            actorUserId: $context->userId,
            eventType: 'inventory_item.status_changed',
            targetType: 'inventory_item',
            targetId: $itemId,
            outcome: 'success',
            metadata: ['status' => $status->value],
        );

        return $item;
    }
}
