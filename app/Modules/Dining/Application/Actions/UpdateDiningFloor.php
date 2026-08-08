<?php

declare(strict_types=1);

namespace App\Modules\Dining\Application\Actions;

use App\Modules\Dining\Application\Data\DiningFloorInput;
use App\Modules\Dining\Application\Exceptions\DiningException;
use App\Modules\Dining\Domain\Models\DiningFloor;
use App\Modules\Tenancy\Application\Contracts\TenantCatalogReference;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;

final readonly class UpdateDiningFloor
{
    public function __construct(
        private TenantPermissionGuard $permissions,
        private TenantCatalogReference $tenancy,
    ) {}

    public function handle(TenantRequestContext $context, string $floorId, DiningFloorInput $input): DiningFloor
    {
        $this->permissions->authorizeManageOutlets($context);
        $floor = DiningFloor::query()
            ->where('tenant_id', $context->tenantId)
            ->whereKey($floorId)
            ->first();

        if (! $floor instanceof DiningFloor) {
            throw DiningException::floorNotFound();
        }

        if (! $this->tenancy->activeOutletExists($context->tenantId, $input->outletId)) {
            throw DiningException::outletNotFound();
        }

        if ($floor->outlet_id !== $input->outletId) {
            throw DiningException::floorOutletCannotChange();
        }

        $code = $this->normalizeCode($input->code);
        $exists = DiningFloor::query()
            ->where('tenant_id', $context->tenantId)
            ->where('outlet_id', $input->outletId)
            ->where('code', $code)
            ->whereKeyNot($floorId)
            ->exists();

        if ($exists) {
            throw DiningException::floorCodeUnavailable();
        }

        $floor->forceFill([
            'outlet_id' => $input->outletId,
            'name' => trim($input->name),
            'code' => $code,
            'display_order' => $input->displayOrder,
        ])->save();

        return $floor;
    }

    private function normalizeCode(string $code): string
    {
        return mb_strtoupper(trim($code));
    }
}
