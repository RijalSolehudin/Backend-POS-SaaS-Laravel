<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Actions;

use App\Modules\Tenancy\Application\Contracts\TenancyAuditRecorder;
use App\Modules\Tenancy\Application\Data\TenancyAuditData;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Exceptions\TenancyException;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use App\Modules\Tenancy\Domain\Enums\PosDeviceStatus;
use App\Modules\Tenancy\Domain\Models\PosDevice;
use App\Shared\Application\Context\ActorContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final readonly class RevokePosDevice
{
    public function __construct(
        private TenantPermissionGuard $guard,
        private TenancyAuditRecorder $audit,
    ) {}

    public function handle(
        TenantRequestContext $context,
        string $deviceId,
        string $reason,
        ActorContext $actor,
    ): void {
        $this->guard->authorizeRevokeDevices($context);
        $reason = trim($reason);
        Validator::make(['reason' => $reason], [
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ])->validate();

        DB::transaction(function () use ($context, $deviceId, $reason, $actor): void {
            $device = PosDevice::query()
                ->where('tenant_id', $context->tenantId)
                ->whereKey($deviceId)
                ->lockForUpdate()
                ->first();
            if (! $device instanceof PosDevice) {
                throw TenancyException::deviceNotFound();
            }

            $changed = $device->status !== PosDeviceStatus::Revoked;
            if ($changed) {
                $device->forceFill([
                    'status' => PosDeviceStatus::Revoked,
                    'revoked_at' => now(),
                    'revoked_by' => $context->userId,
                    'revoked_reason' => $reason,
                ])->save();
            }

            DB::table('personal_access_tokens')->where('pos_device_id', $deviceId)->delete();

            $this->audit->record(new TenancyAuditData(
                eventType: $changed ? 'pos_device.revoked' : 'pos_device.revoke_replayed',
                outcome: 'success',
                actorType: $actor->actorType,
                actorId: $actor->actorId,
                correlationId: $actor->correlationId,
                targetTenantId: $context->tenantId,
                reason: $reason,
                metadata: [
                    'device_id' => $deviceId,
                    'outlet_id' => $device->outlet_id,
                ],
            ));
        });
    }
}
