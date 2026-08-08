<?php

declare(strict_types=1);

namespace App\Modules\Dining\Application\Actions;

use App\Modules\Dining\Application\Exceptions\DiningException;
use App\Modules\Dining\Domain\Enums\TableStatus;
use App\Modules\Dining\Domain\Models\DiningFloor;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;

final readonly class ChangeDiningFloorStatus
{
    public function __construct(private TenantPermissionGuard $permissions) {}

    public function handle(TenantRequestContext $context, string $floorId, TableStatus $status): DiningFloor
    {
        $this->permissions->authorizeManageOutlets($context);
        $floor = DiningFloor::query()
            ->where('tenant_id', $context->tenantId)
            ->whereKey($floorId)
            ->first();

        if (! $floor instanceof DiningFloor) {
            throw DiningException::floorNotFound();
        }

        $floor->forceFill(['status' => $status])->save();

        return $floor;
    }
}
