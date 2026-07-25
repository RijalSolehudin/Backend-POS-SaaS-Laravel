<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Actions;

use App\Modules\Identity\Application\Contracts\UserAccessRevoker;
use App\Modules\Identity\Application\Services\TenantPasswordPolicy;
use App\Modules\Identity\Domain\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final readonly class ChangeTenantPassword
{
    public function __construct(
        private TenantPasswordPolicy $passwordPolicy,
        private UserAccessRevoker $accessRevoker,
    ) {}

    public function handle(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $this->passwordPolicy->validate($newPassword);

        $user->forceFill([
            'password' => $newPassword,
            'must_change_password' => false,
            'password_changed_at' => now(),
        ])->save();

        $this->accessRevoker->revokeAll((string) $user->getKey());
    }
}
