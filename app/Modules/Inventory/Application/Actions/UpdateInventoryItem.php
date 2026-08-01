<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Actions;

use App\Modules\Inventory\Application\Data\InventoryItemInput;
use App\Modules\Inventory\Application\Exceptions\InventoryException;
use App\Modules\Inventory\Domain\Models\InventoryItem;
use App\Modules\Inventory\Domain\Models\InventoryUnit;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;

final readonly class UpdateInventoryItem
{
    public function __construct(
        private TenantPermissionGuard $permissions,
        private RecordInventoryAuditEvent $audit,
    ) {}

    public function handle(TenantRequestContext $context, string $itemId, InventoryItemInput $input): InventoryItem
    {
        $this->permissions->authorizeManageCatalog($context);
        $this->ensureUnit($context, $input->unitId);

        $item = InventoryItem::query()
            ->where('tenant_id', $context->tenantId)
            ->whereKey($itemId)
            ->first();

        if (! $item instanceof InventoryItem) {
            throw InventoryException::itemNotFound();
        }

        $sku = $this->normalizeSku($input->sku);
        $exists = InventoryItem::query()
            ->where('tenant_id', $context->tenantId)
            ->where('sku', $sku)
            ->whereKeyNot($itemId)
            ->exists();

        if ($exists) {
            throw InventoryException::skuUnavailable();
        }

        $item->forceFill([
            'unit_id' => $input->unitId,
            'name' => trim($input->name),
            'sku' => $sku,
        ])->save();

        $this->audit->handle(
            tenantId: $context->tenantId,
            outletId: null,
            actorUserId: $context->userId,
            eventType: 'inventory_item.updated',
            targetType: 'inventory_item',
            targetId: $itemId,
            outcome: 'success',
            metadata: ['sku' => $item->sku, 'unit_id' => $item->unit_id],
        );

        return $item;
    }

    private function ensureUnit(TenantRequestContext $context, string $unitId): void
    {
        $unit = InventoryUnit::query()->whereKey($unitId)->first();

        if (! $unit instanceof InventoryUnit) {
            throw InventoryException::unitNotFound();
        }

        if ($unit->tenant_id !== $context->tenantId) {
            throw InventoryException::crossTenantReference();
        }
    }

    private function normalizeSku(string $sku): string
    {
        return mb_strtoupper(trim($sku));
    }
}
