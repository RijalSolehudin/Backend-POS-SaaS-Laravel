<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Actions;

use App\Modules\Tenancy\Application\Contracts\TenancyAuditRecorder;
use App\Modules\Tenancy\Application\Data\DisableTenantResult;
use App\Modules\Tenancy\Application\Data\TenancyAuditData;
use App\Modules\Tenancy\Application\Exceptions\TenantProvisioningException;
use App\Modules\Tenancy\Domain\Enums\TenantStatus;
use App\Modules\Tenancy\Domain\Models\Tenant;
use App\Shared\Application\Context\ActorContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final readonly class DisableTenant
{
    public function __construct(
        private TenancyAuditRecorder $audit,
    ) {}

    public function handle(string $tenantId, string $reason, ActorContext $actor): DisableTenantResult
    {
        $reason = trim($reason);

        Validator::make(
            ['reason' => $reason],
            ['reason' => ['required', 'string', 'min:10', 'max:500']],
        )->validate();

        return DB::transaction(function () use ($tenantId, $reason, $actor): DisableTenantResult {
            $tenant = Tenant::query()->whereKey($tenantId)->lockForUpdate()->first();

            if (! $tenant instanceof Tenant) {
                throw TenantProvisioningException::tenantNotFound();
            }

            $wasChanged = $tenant->status !== TenantStatus::Disabled;

            if ($wasChanged) {
                $tenant->forceFill([
                    'status' => TenantStatus::Disabled,
                    'disabled_at' => now(),
                    'disabled_reason' => $reason,
                ])->save();
            }

            $this->audit->record(new TenancyAuditData(
                eventType: $wasChanged ? 'tenant.disabled' : 'tenant.disable_replayed',
                outcome: 'success',
                actorType: $actor->actorType,
                actorId: $actor->actorId,
                correlationId: $actor->correlationId,
                targetTenantId: (string) $tenant->getKey(),
                reason: $reason,
            ));

            return new DisableTenantResult(
                tenantId: (string) $tenant->getKey(),
                wasChanged: $wasChanged,
            );
        });
    }
}
