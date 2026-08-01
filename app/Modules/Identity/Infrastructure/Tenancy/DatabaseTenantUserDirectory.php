<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Tenancy;

use App\Modules\Identity\Domain\Enums\UserStatus;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Identity\Domain\Models\UserRoleAssignment;
use App\Modules\Tenancy\Application\Contracts\TenantUserDirectory;
use App\Modules\Tenancy\Application\Data\TenantUserSummary;

final class DatabaseTenantUserDirectory implements TenantUserDirectory
{
    public function findForTenant(string $tenantId, string $userId): ?TenantUserSummary
    {
        $user = User::query()
            ->join('tenant_memberships', 'tenant_memberships.user_id', '=', 'users.id')
            ->where('tenant_memberships.tenant_id', $tenantId)
            ->where('users.id', $userId)
            ->first(['users.*']);

        if (! $user instanceof User) {
            return null;
        }

        return new TenantUserSummary(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            active: $user->status === UserStatus::Active,
            roles: $this->rolesForUser($user->id),
        );
    }

    public function listForTenant(string $tenantId): array
    {
        $users = User::query()
            ->join('tenant_memberships', 'tenant_memberships.user_id', '=', 'users.id')
            ->where('tenant_memberships.tenant_id', $tenantId)
            ->orderBy('users.name')
            ->get(['users.*'])
            ->map(fn (User $user): TenantUserSummary => new TenantUserSummary(
                id: $user->id,
                name: $user->name,
                email: $user->email,
                active: $user->status === UserStatus::Active,
                roles: $this->rolesForUser($user->id),
            ))
            ->values()
            ->all();

        return array_values($users);
    }

    /**
     * @return list<string>
     */
    private function rolesForUser(string $userId): array
    {
        $roles = UserRoleAssignment::query()
            ->where('user_id', $userId)
            ->orderBy('role')
            ->pluck('role')
            ->map(fn (mixed $role): string => is_string($role) ? $role : (string) $role->value)
            ->values()
            ->all();

        return array_values($roles);
    }
}
