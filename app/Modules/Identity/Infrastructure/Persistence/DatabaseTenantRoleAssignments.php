<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence;

use App\Modules\Identity\Application\Contracts\TenantRoleAssignments;
use App\Modules\Identity\Domain\Enums\PredefinedRole;
use App\Modules\Identity\Domain\Models\UserRoleAssignment;

final class DatabaseTenantRoleAssignments implements TenantRoleAssignments
{
    public function rolesForUser(string $userId): array
    {
        $roles = UserRoleAssignment::query()
            ->where('user_id', $userId)
            ->orderBy('role')
            ->pluck('role')
            ->map(fn (PredefinedRole|string $role): string => $role instanceof PredefinedRole ? $role->value : $role)
            ->values()
            ->all();

        return array_values($roles);
    }

    public function hasRole(string $userId, string $role): bool
    {
        return UserRoleAssignment::query()
            ->where('user_id', $userId)
            ->where('role', $role)
            ->exists();
    }

    public function assign(string $userId, string $role): bool
    {
        $assignment = UserRoleAssignment::query()->firstOrCreate([
            'user_id' => $userId,
            'role' => PredefinedRole::from($role),
        ]);

        return $assignment->wasRecentlyCreated;
    }

    public function remove(string $userId, string $role): bool
    {
        return UserRoleAssignment::query()
            ->where('user_id', $userId)
            ->where('role', $role)
            ->delete() > 0;
    }
}
