<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Actions;

use App\Modules\Tenancy\Application\Contracts\TenancyAuditRecorder;
use App\Modules\Tenancy\Application\Data\TenancyAuditData;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Exceptions\TenancyException;
use App\Modules\Tenancy\Application\Services\TenantOwnerGuard;
use App\Modules\Tenancy\Domain\Models\Outlet;
use App\Modules\Tenancy\Domain\Models\OutletUserAssignment;
use App\Shared\Application\Context\ActorContext;
use Illuminate\Support\Facades\DB;

final readonly class RemoveUserFromOutlet
{
    public function __construct(
        private TenantOwnerGuard $ownerGuard,
        private TenancyAuditRecorder $audit,
    ) {}

    public function handle(
        TenantRequestContext $context,
        string $outletId,
        string $userId,
        ActorContext $actor,
    ): void {
        $this->ownerGuard->authorize($context);

        DB::transaction(function () use ($context, $outletId, $userId, $actor): void {
            if (! Outlet::query()->where('tenant_id', $context->tenantId)->whereKey($outletId)->exists()) {
                throw TenancyException::outletNotFound();
            }

            $deleted = OutletUserAssignment::query()
                ->where('tenant_id', $context->tenantId)
                ->where('outlet_id', $outletId)
                ->where('user_id', $userId)
                ->delete() > 0;

            $this->audit->record(new TenancyAuditData(
                eventType: $deleted ? 'outlet_user.removed' : 'outlet_user.remove_replayed',
                outcome: 'success',
                actorType: $actor->actorType,
                actorId: $actor->actorId,
                correlationId: $actor->correlationId,
                targetTenantId: $context->tenantId,
                metadata: ['outlet_id' => $outletId, 'user_id' => $userId],
            ));

        });
    }
}
