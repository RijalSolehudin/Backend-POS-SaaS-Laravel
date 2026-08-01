<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Actions;

use App\Modules\Identity\Application\Contracts\TenantRoleAssignments;
use App\Modules\Identity\Application\Services\PredefinedTenantRolePolicy;
use App\Modules\Tenancy\Application\Contracts\TenancyAuditRecorder;
use App\Modules\Tenancy\Application\Data\TenancyAuditData;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Exceptions\TenancyException;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use App\Modules\Tenancy\Domain\Models\TenantMembership;
use App\Shared\Application\Context\ActorContext;
use Illuminate\Support\Facades\DB;

final readonly class AssignPredefinedRole
{
    public function __construct(
        private TenantPermissionGuard $guard,
        private PredefinedTenantRolePolicy $policy,
        private TenantRoleAssignments $roles,
        private TenancyAuditRecorder $audit,
    ) {}

    public function handle(
        TenantRequestContext $context,
        string $targetUserId,
        string $role,
        ActorContext $actor,
    ): void {
        $this->guard->authorizeManageTenantRoles($context);

        if (! $this->policy->isPredefinedRole($role)) {
            throw TenancyException::invalidRole();
        }

        DB::transaction(function () use ($context, $targetUserId, $role, $actor): void {
            $membershipExists = TenantMembership::query()
                ->where('tenant_id', $context->tenantId)
                ->where('user_id', $targetUserId)
                ->exists();
            if (! $membershipExists) {
                throw TenancyException::userNotFound();
            }

            $created = $this->roles->assign($targetUserId, $role);

            $this->audit->record(new TenancyAuditData(
                eventType: $created ? 'tenant_role.assigned' : 'tenant_role.assign_replayed',
                outcome: 'success',
                actorType: $actor->actorType,
                actorId: $actor->actorId,
                correlationId: $actor->correlationId,
                targetTenantId: $context->tenantId,
                metadata: [
                    'target_user_id' => $targetUserId,
                    'role' => $role,
                ],
            ));
        });
    }
}
