<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Actions;

use App\Modules\Inventory\Application\Data\InventoryUnitInput;
use App\Modules\Inventory\Application\Exceptions\InventoryException;
use App\Modules\Inventory\Domain\Models\InventoryUnit;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;

final readonly class UpdateInventoryUnit
{
    public function __construct(
        private TenantPermissionGuard $permissions,
        private RecordInventoryAuditEvent $audit,
    ) {}

    public function handle(TenantRequestContext $context, string $unitId, InventoryUnitInput $input): InventoryUnit
    {
        $this->permissions->authorizeManageCatalog($context);

        $unit = InventoryUnit::query()
            ->where('tenant_id', $context->tenantId)
            ->whereKey($unitId)
            ->first();

        if (! $unit instanceof InventoryUnit) {
            throw InventoryException::unitNotFound();
        }

        $symbol = $this->normalizeSymbol($input->symbol);
        $exists = InventoryUnit::query()
            ->where('tenant_id', $context->tenantId)
            ->where('symbol', $symbol)
            ->whereKeyNot($unitId)
            ->exists();

        if ($exists) {
            throw InventoryException::unitSymbolUnavailable();
        }

        $unit->forceFill([
            'name' => trim($input->name),
            'symbol' => $symbol,
            'precision' => $input->precision,
        ])->save();

        $this->audit->handle(
            tenantId: $context->tenantId,
            outletId: null,
            actorUserId: $context->userId,
            eventType: 'inventory_unit.updated',
            targetType: 'inventory_unit',
            targetId: $unitId,
            outcome: 'success',
            metadata: ['symbol' => $unit->symbol],
        );

        return $unit;
    }

    private function normalizeSymbol(string $symbol): string
    {
        return mb_strtolower(trim($symbol));
    }
}
