<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Actions;

use App\Modules\Inventory\Application\Data\InventoryUnitInput;
use App\Modules\Inventory\Application\Exceptions\InventoryException;
use App\Modules\Inventory\Domain\Enums\InventoryStatus;
use App\Modules\Inventory\Domain\Models\InventoryUnit;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;

final readonly class CreateInventoryUnit
{
    public function __construct(
        private TenantPermissionGuard $permissions,
        private RecordInventoryAuditEvent $audit,
    ) {}

    public function handle(TenantRequestContext $context, InventoryUnitInput $input): InventoryUnit
    {
        $this->permissions->authorizeManageCatalog($context);
        $this->ensureSymbolAvailable($context, $input->symbol);

        $unit = InventoryUnit::query()->create([
            'tenant_id' => $context->tenantId,
            'name' => trim($input->name),
            'symbol' => $this->normalizeSymbol($input->symbol),
            'precision' => $input->precision,
            'status' => InventoryStatus::Active,
        ]);

        $this->audit->handle(
            tenantId: $context->tenantId,
            outletId: null,
            actorUserId: $context->userId,
            eventType: 'inventory_unit.created',
            targetType: 'inventory_unit',
            targetId: (string) $unit->getKey(),
            outcome: 'success',
            metadata: ['symbol' => $unit->symbol],
        );

        return $unit;
    }

    private function ensureSymbolAvailable(TenantRequestContext $context, string $symbol): void
    {
        $exists = InventoryUnit::query()
            ->where('tenant_id', $context->tenantId)
            ->where('symbol', $this->normalizeSymbol($symbol))
            ->exists();

        if ($exists) {
            throw InventoryException::unitSymbolUnavailable();
        }
    }

    private function normalizeSymbol(string $symbol): string
    {
        return mb_strtolower(trim($symbol));
    }
}
