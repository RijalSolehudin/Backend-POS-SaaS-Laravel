<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Actions;

use App\Modules\Tenancy\Application\Contracts\TenancyAuditRecorder;
use App\Modules\Tenancy\Application\Data\TenancyAuditData;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Exceptions\TenancyException;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use App\Modules\Tenancy\Domain\Enums\PosDeviceStatus;
use App\Modules\Tenancy\Domain\Models\Outlet;
use App\Modules\Tenancy\Domain\Models\PosDevice;
use App\Shared\Application\Context\ActorContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final readonly class ReassignPosDevice
{
    public function __construct(
        private TenantPermissionGuard $guard,
        private TenancyAuditRecorder $audit,
    ) {}

    public function handle(
        TenantRequestContext $context,
        string $deviceId,
        string $outletId,
        string $reason,
        ActorContext $actor,
    ): PosDevice {
        $this->guard->authorizeReassignDevices($context);
        $reason = trim($reason);
        Validator::make(['reason' => $reason], [
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ])->validate();

        return DB::transaction(function () use ($context, $deviceId, $outletId, $reason, $actor): PosDevice {
            $device = PosDevice::query()
                ->where('tenant_id', $context->tenantId)
                ->whereKey($deviceId)
                ->lockForUpdate()
                ->first();
            if (! $device instanceof PosDevice) {
                throw TenancyException::deviceNotFound();
            }

            if ($device->status === PosDeviceStatus::Revoked) {
                throw TenancyException::deviceNotFound();
            }

            if (! Outlet::query()->where('tenant_id', $context->tenantId)->whereKey($outletId)->exists()) {
                throw TenancyException::outletNotFound();
            }

            $previousOutletId = $device->outlet_id;
            $changed = $previousOutletId !== $outletId;
            if ($changed) {
                $device->forceFill(['outlet_id' => $outletId])->save();
            }

            $this->revokeLinkedTokens($deviceId);

            $this->audit->record(new TenancyAuditData(
                eventType: $changed ? 'pos_device.reassigned' : 'pos_device.reassign_replayed',
                outcome: 'success',
                actorType: $actor->actorType,
                actorId: $actor->actorId,
                correlationId: $actor->correlationId,
                targetTenantId: $context->tenantId,
                reason: $reason,
                metadata: [
                    'device_id' => $deviceId,
                    'from_outlet_id' => $previousOutletId,
                    'to_outlet_id' => $outletId,
                ],
            ));

            return $device;
        });
    }

    private function revokeLinkedTokens(string $deviceId): void
    {
        DB::table('personal_access_tokens')->where('pos_device_id', $deviceId)->delete();
    }
}
