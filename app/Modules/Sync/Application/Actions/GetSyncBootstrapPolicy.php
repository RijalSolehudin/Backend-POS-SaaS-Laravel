<?php

declare(strict_types=1);

namespace App\Modules\Sync\Application\Actions;

use App\Modules\Tenancy\Domain\Enums\PosDeviceStatus;
use App\Modules\Tenancy\Domain\Models\PosDevice;

final readonly class GetSyncBootstrapPolicy
{
    /**
     * @return array<string, mixed>
     */
    public function handle(string $tenantId, string $outletId, string $deviceId): array
    {
        $device = PosDevice::query()
            ->where('tenant_id', $tenantId)
            ->where('outlet_id', $outletId)
            ->whereKey($deviceId)
            ->first();

        return [
            'device_id' => $deviceId,
            'device_revoked' => ! $device instanceof PosDevice || $device->status === PosDeviceStatus::Revoked,
            'local_cache_retention_hours' => (int) config('sync.local_cache_retention_hours', 72),
            'local_order_retention_hours' => (int) config('sync.local_order_retention_hours', 168),
            'requires_local_encryption' => true,
            'server_accepts_local_encryption_keys' => false,
        ];
    }
}
