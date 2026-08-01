<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Actions;

use App\Modules\Inventory\Application\Data\InventoryItemInput;
use App\Modules\Inventory\Application\Exceptions\InventoryException;
use App\Modules\Inventory\Domain\Enums\InventoryStatus;
use App\Modules\Inventory\Domain\Models\InventoryItem;
use App\Modules\Inventory\Domain\Models\InventoryUnit;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;

final readonly class CreateInventoryItem
{
    public function __construct(
        private TenantPermissionGuard $permissions,
        private RecordInventoryAuditEvent $audit,
    ) {}

    public function handle(TenantRequestContext $context, InventoryItemInput $input): InventoryItem
    {
        $this->permissions->authorizeManageCatalog($context);
        $this->ensureUnit($context, $input->unitId);
        $this->ensureSkuAvailable($context, $input->sku);

        $item = InventoryItem::query()->create([
            'tenant_id' => $context->tenantId,
            'unit_id' => $input->unitId,
            'name' => trim($input->name),
            'sku' => $this->normalizeSku($input->sku),
            'status' => InventoryStatus::Active,
        ]);

        $this->audit->handle(
            tenantId: $context->tenantId,
            outletId: null,
            actorUserId: $context->userId,
            eventType: 'inventory_item.created',
            targetType: 'inventory_item',
            targetId: (string) $item->getKey(),
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

    private function ensureSkuAvailable(TenantRequestContext $context, string $sku): void
    {
        $exists = InventoryItem::query()
            ->where('tenant_id', $context->tenantId)
            ->where('sku', $this->normalizeSku($sku))
            ->exists();

        if ($exists) {
            throw InventoryException::skuUnavailable();
        }
    }

    private function normalizeSku(string $sku): string
    {
        return mb_strtoupper(trim($sku));
    }
}
