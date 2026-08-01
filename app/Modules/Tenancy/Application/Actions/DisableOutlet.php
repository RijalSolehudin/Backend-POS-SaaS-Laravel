<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Actions;

use App\Modules\Tenancy\Application\Contracts\TenancyAuditRecorder;
use App\Modules\Tenancy\Application\Data\TenancyAuditData;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Exceptions\TenancyException;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use App\Modules\Tenancy\Domain\Enums\OutletStatus;
use App\Modules\Tenancy\Domain\Models\Outlet;
use App\Shared\Application\Context\ActorContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final readonly class DisableOutlet
{
    public function __construct(
        private TenantPermissionGuard $guard,
        private TenancyAuditRecorder $audit,
    ) {}

    public function handle(
        TenantRequestContext $context,
        string $outletId,
        string $reason,
        ActorContext $actor,
    ): void {
        $this->guard->authorizeManageOutlets($context);
        $reason = trim($reason);
        Validator::make(['reason' => $reason], [
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ])->validate();

        DB::transaction(function () use ($context, $outletId, $reason, $actor): void {
            $outlet = Outlet::query()
                ->where('tenant_id', $context->tenantId)
                ->whereKey($outletId)
                ->lockForUpdate()
                ->first();

            if (! $outlet instanceof Outlet) {
                throw TenancyException::outletNotFound();
            }

            $changed = $outlet->status !== OutletStatus::Disabled;
            if ($changed) {
                $outlet->forceFill(['status' => OutletStatus::Disabled])->save();
            }

            $this->audit->record(new TenancyAuditData(
                eventType: $changed ? 'outlet.disabled' : 'outlet.disable_replayed',
                outcome: 'success',
                actorType: $actor->actorType,
                actorId: $actor->actorId,
                correlationId: $actor->correlationId,
                targetTenantId: $context->tenantId,
                reason: $reason,
                metadata: ['outlet_id' => $outletId],
            ));

        });
    }
}
