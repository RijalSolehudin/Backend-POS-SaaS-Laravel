<?php

declare(strict_types=1);

namespace App\Modules\Dining\Application\Actions;

use App\Modules\Dining\Application\Data\DiningTableInput;
use App\Modules\Dining\Application\Exceptions\DiningException;
use App\Modules\Dining\Domain\Models\DiningFloor;
use App\Modules\Dining\Domain\Models\DiningTable;
use App\Modules\Tenancy\Application\Contracts\TenantCatalogReference;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;

final readonly class UpdateDiningTable
{
    public function __construct(
        private TenantPermissionGuard $permissions,
        private TenantCatalogReference $tenancy,
    ) {}

    public function handle(TenantRequestContext $context, string $tableId, DiningTableInput $input): DiningTable
    {
        $this->permissions->authorizeManageOutlets($context);
        $table = DiningTable::query()
            ->where('tenant_id', $context->tenantId)
            ->whereKey($tableId)
            ->first();

        if (! $table instanceof DiningTable) {
            throw DiningException::tableNotFound();
        }

        if (! $this->tenancy->activeOutletExists($context->tenantId, $input->outletId)) {
            throw DiningException::outletNotFound();
        }

        $floor = DiningFloor::query()
            ->where('tenant_id', $context->tenantId)
            ->whereKey($input->floorId)
            ->first();

        if (! $floor instanceof DiningFloor) {
            throw DiningException::floorNotFound();
        }

        if ($floor->outlet_id !== $input->outletId) {
            throw DiningException::crossOutletFloor();
        }

        $code = $this->normalizeCode($input->code);
        $exists = DiningTable::query()
            ->where('tenant_id', $context->tenantId)
            ->where('outlet_id', $input->outletId)
            ->where('code', $code)
            ->whereKeyNot($tableId)
            ->exists();

        if ($exists) {
            throw DiningException::tableCodeUnavailable();
        }

        $table->forceFill([
            'outlet_id' => $input->outletId,
            'floor_id' => $input->floorId,
            'name' => trim($input->name),
            'code' => $code,
            'capacity' => $input->capacity,
            'display_order' => $input->displayOrder,
        ])->save();

        return $table;
    }

    private function normalizeCode(string $code): string
    {
        return mb_strtoupper(trim($code));
    }
}
