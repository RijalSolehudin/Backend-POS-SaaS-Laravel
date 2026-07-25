<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Actions;

use App\Modules\Identity\Application\Contracts\TenantAccessResolver;
use App\Modules\Identity\Application\Data\AuthenticatedTenantUser;
use App\Modules\Identity\Domain\Models\User;
use Illuminate\Support\Facades\Hash;

final readonly class AuthenticateTenantUser
{
    public function __construct(private TenantAccessResolver $access) {}

    public function handle(string $email, string $password): ?AuthenticatedTenantUser
    {
        $user = User::query()->where('email', mb_strtolower(trim($email)))->first();

        if (! $user instanceof User || ! Hash::check($password, $user->password) || ! $user->isActive()) {
            return null;
        }

        $access = $this->access->forUser((string) $user->getKey());

        if ($access === null || ! $access->tenantActive) {
            return null;
        }

        return new AuthenticatedTenantUser(
            userId: (string) $user->getKey(),
            tenantId: $access->tenantId,
            mustChangePassword: $user->must_change_password,
        );
    }
}
