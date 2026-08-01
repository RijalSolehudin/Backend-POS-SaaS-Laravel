<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Actions;

use App\Modules\Inventory\Application\Exceptions\InventoryException;
use App\Modules\Inventory\Domain\Enums\InventoryStatus;
use App\Modules\Inventory\Domain\Models\InventoryUnit;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;

final readonly class ChangeInventoryUnitStatus
{
    public function __construct(
        private TenantPermissionGuard $permissions,
        private RecordInventoryAuditEvent $audit,
    ) {}

    public function handle(TenantRequestContext $context, string $unitId, InventoryStatus $status): InventoryUnit
    {
        $this->permissions->authorizeManageCatalog($context);

        $unit = InventoryUnit::query()
            ->where('tenant_id', $context->tenantId)
            ->whereKey($unitId)
            ->first();

        if (! $unit instanceof InventoryUnit) {
            throw InventoryException::unitNotFound();
        }

        $unit->forceFill(['status' => $status])->save();

        $this->audit->handle(
            tenantId: $context->tenantId,
            outletId: null,
            actorUserId: $context->userId,
            eventType: 'inventory_unit.status_changed',
            targetType: 'inventory_unit',
            targetId: $unitId,
            outcome: 'success',
            metadata: ['status' => $status->value],
        );

        return $unit;
    }
}
