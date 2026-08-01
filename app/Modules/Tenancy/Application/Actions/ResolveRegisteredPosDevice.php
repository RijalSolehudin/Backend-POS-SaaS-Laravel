<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Actions;

use App\Modules\Tenancy\Application\Exceptions\DeviceRegistryException;
use App\Modules\Tenancy\Domain\Enums\PosDeviceStatus;
use App\Modules\Tenancy\Domain\Models\PosDevice;

final readonly class ResolveRegisteredPosDevice
{
    public function handle(string $tenantId, string $installationId): PosDevice
    {
        $device = PosDevice::query()
            ->where('tenant_id', $tenantId)
            ->where('installation_id', mb_strtolower(trim($installationId)))
            ->first();

        if (! $device instanceof PosDevice) {
            throw DeviceRegistryException::notRegistered();
        }

        if ($device->status === PosDeviceStatus::Revoked) {
            throw DeviceRegistryException::revoked();
        }

        return $device;
    }
}
