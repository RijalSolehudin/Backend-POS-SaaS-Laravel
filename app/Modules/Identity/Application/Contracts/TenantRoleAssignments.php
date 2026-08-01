<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Contracts;

interface TenantRoleAssignments
{
    /**
     * @return list<string>
     */
    public function rolesForUser(string $userId): array;

    public function hasRole(string $userId, string $role): bool;

    public function assign(string $userId, string $role): bool;

    public function remove(string $userId, string $role): bool;
}
