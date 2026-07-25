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
use App\Modules\Tenancy\Domain\Models\TenantMembership;
use App\Shared\Application\Context\ActorContext;
use Illuminate\Support\Facades\DB;

final readonly class AssignUserToOutlet
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
    ): OutletUserAssignment {
        $this->ownerGuard->authorize($context);

        return DB::transaction(function () use ($context, $outletId, $userId, $actor): OutletUserAssignment {
            $outletExists = Outlet::query()
                ->where('tenant_id', $context->tenantId)
                ->whereKey($outletId)
                ->exists();
            if (! $outletExists) {
                throw TenancyException::outletNotFound();
            }

            $membershipExists = TenantMembership::query()
                ->where('tenant_id', $context->tenantId)
                ->where('user_id', $userId)
                ->exists();
            if (! $membershipExists) {
                throw TenancyException::userNotFound();
            }

            $assignment = OutletUserAssignment::query()->firstOrCreate([
                'tenant_id' => $context->tenantId,
                'outlet_id' => $outletId,
                'user_id' => $userId,
            ]);

            $this->audit->record(new TenancyAuditData(
                eventType: $assignment->wasRecentlyCreated ? 'outlet_user.assigned' : 'outlet_user.assign_replayed',
                outcome: 'success',
                actorType: $actor->actorType,
                actorId: $actor->actorId,
                correlationId: $actor->correlationId,
                targetTenantId: $context->tenantId,
                metadata: ['outlet_id' => $outletId, 'user_id' => $userId],
            ));

            return $assignment;
        });
    }
}
