<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Actions;

use App\Modules\Tenancy\Application\Contracts\TenancyAuditRecorder;
use App\Modules\Tenancy\Application\Data\TenancyAuditData;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Exceptions\TenancyException;
use App\Modules\Tenancy\Application\Services\PosDeviceInput;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use App\Modules\Tenancy\Domain\Enums\PosDeviceStatus;
use App\Modules\Tenancy\Domain\Models\Outlet;
use App\Modules\Tenancy\Domain\Models\PosDevice;
use App\Shared\Application\Context\ActorContext;
use Illuminate\Support\Facades\DB;

final readonly class RegisterPosDevice
{
    public function __construct(
        private TenantPermissionGuard $guard,
        private PosDeviceInput $input,
        private TenancyAuditRecorder $audit,
    ) {}

    public function handle(
        TenantRequestContext $context,
        string $outletId,
        string $installationId,
        string $name,
        string $platform,
        ?string $appVersion,
        ActorContext $actor,
    ): PosDevice {
        $this->guard->authorizeRegisterDevice($context, $outletId);
        $input = $this->input->validate($installationId, $name, $platform, $appVersion);

        return DB::transaction(function () use ($context, $outletId, $input, $actor): PosDevice {
            if (! Outlet::query()->where('tenant_id', $context->tenantId)->whereKey($outletId)->exists()) {
                throw TenancyException::outletNotFound();
            }

            if (PosDevice::query()
                ->where('tenant_id', $context->tenantId)
                ->where('installation_id', $input['installation_id'])
                ->exists()) {
                throw TenancyException::deviceInstallationUnavailable();
            }

            $device = PosDevice::query()->create([
                'installation_id' => $input['installation_id'],
                'tenant_id' => $context->tenantId,
                'outlet_id' => $outletId,
                'name' => $input['name'],
                'client_type' => 'pos_terminal',
                'platform' => $input['platform'],
                'app_version' => $input['app_version'],
                'status' => PosDeviceStatus::Active,
                'registered_by' => $context->userId,
            ]);

            $this->audit->record(new TenancyAuditData(
                eventType: 'pos_device.registered',
                outcome: 'success',
                actorType: $actor->actorType,
                actorId: $actor->actorId,
                correlationId: $actor->correlationId,
                targetTenantId: $context->tenantId,
                metadata: [
                    'device_id' => (string) $device->getKey(),
                    'outlet_id' => $outletId,
                    'installation_id' => $input['installation_id'],
                ],
            ));

            return $device;
        });
    }
}
