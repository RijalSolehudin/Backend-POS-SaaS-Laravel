<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Actions;

use App\Modules\Tenancy\Application\Data\PosOutletContext;
use App\Modules\Tenancy\Application\Exceptions\DeviceRegistryException;
use App\Modules\Tenancy\Application\Exceptions\TenancyException;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use App\Modules\Tenancy\Domain\Enums\OutletStatus;
use App\Modules\Tenancy\Domain\Enums\PosDeviceStatus;
use App\Modules\Tenancy\Domain\Models\Outlet;
use App\Modules\Tenancy\Domain\Models\PosDevice;

final readonly class ResolvePosOutletApiContext
{
    public function __construct(
        private ResolveTenantRequestContext $tenantContext,
        private TenantPermissionGuard $permissions,
    ) {}

    public function handle(string $userId, string $deviceId, string $outletId): PosOutletContext
    {
        $context = $this->tenantContext->handle($userId);

        if ($context === null) {
            throw TenancyException::forbidden();
        }

        $device = PosDevice::query()
            ->where('tenant_id', $context->tenantId)
            ->whereKey($deviceId)
            ->first();

        if (! $device instanceof PosDevice) {
            throw DeviceRegistryException::notRegistered();
        }

        if ($device->status !== PosDeviceStatus::Active) {
            throw DeviceRegistryException::revoked();
        }

        if ($device->outlet_id !== $outletId) {
            throw TenancyException::outletNotFound();
        }

        $outlet = Outlet::query()
            ->where('tenant_id', $context->tenantId)
            ->whereKey($outletId)
            ->first();

        if (! $outlet instanceof Outlet || $outlet->status !== OutletStatus::Active) {
            throw TenancyException::outletNotFound();
        }

        $this->permissions->authorizeOperatePos($context, $outletId);

        return new PosOutletContext(
            tenantId: $context->tenantId,
            outletId: $outletId,
            deviceId: $deviceId,
            userId: $userId,
        );
    }
}
