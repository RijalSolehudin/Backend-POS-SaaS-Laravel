<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Actions;

use App\Modules\Inventory\Application\Data\InventoryItemOutletSettingsInput;
use App\Modules\Inventory\Application\Exceptions\InventoryException;
use App\Modules\Inventory\Domain\Models\InventoryItem;
use App\Modules\Inventory\Domain\Models\InventoryItemOutletSetting;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use App\Modules\Tenancy\Domain\Models\Outlet;

final readonly class SetInventoryItemOutletSettings
{
    public function __construct(
        private TenantPermissionGuard $permissions,
        private RecordInventoryAuditEvent $audit,
    ) {}

    public function handle(TenantRequestContext $context, InventoryItemOutletSettingsInput $input): InventoryItemOutletSetting
    {
        $this->permissions->authorizeManageCatalog($context);
        $this->ensureItem($context, $input->itemId);
        $this->ensureOutlet($context, $input->outletId);

        $setting = InventoryItemOutletSetting::query()->updateOrCreate(
            [
                'tenant_id' => $context->tenantId,
                'outlet_id' => $input->outletId,
                'item_id' => $input->itemId,
            ],
            [
                'status' => $input->status,
                'low_stock_threshold_quantity' => $this->normalizeQuantity($input->lowStockThresholdQuantity),
            ],
        );

        $this->audit->handle(
            tenantId: $context->tenantId,
            outletId: $input->outletId,
            actorUserId: $context->userId,
            eventType: 'inventory_item_outlet_settings.updated',
            targetType: 'inventory_item',
            targetId: $input->itemId,
            outcome: 'success',
            metadata: [
                'status' => $input->status->value,
                'low_stock_threshold_quantity' => $setting->low_stock_threshold_quantity,
            ],
        );

        return $setting;
    }

    private function ensureItem(TenantRequestContext $context, string $itemId): void
    {
        $exists = InventoryItem::query()
            ->where('tenant_id', $context->tenantId)
            ->whereKey($itemId)
            ->exists();

        if (! $exists) {
            throw InventoryException::itemNotFound();
        }
    }

    private function ensureOutlet(TenantRequestContext $context, string $outletId): void
    {
        $exists = Outlet::query()
            ->where('tenant_id', $context->tenantId)
            ->whereKey($outletId)
            ->exists();

        if (! $exists) {
            throw InventoryException::outletNotFound();
        }
    }

    private function normalizeQuantity(string $quantity): string
    {
        $normalized = trim($quantity);

        if (! str_contains($normalized, '.')) {
            return $normalized.'.000';
        }

        [$whole, $decimal] = explode('.', $normalized, 2);

        return $whole.'.'.str_pad(substr($decimal, 0, 3), 3, '0');
    }
}
