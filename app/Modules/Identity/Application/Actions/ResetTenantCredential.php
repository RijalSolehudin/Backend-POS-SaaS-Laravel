<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Actions;

use App\Modules\Identity\Application\Contracts\UserAccessRevoker;
use App\Modules\Identity\Application\Services\TenantPasswordPolicy;
use App\Modules\Identity\Domain\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\PasswordBroker;

final readonly class ResetTenantCredential
{
    public function __construct(
        private TenantPasswordPolicy $passwordPolicy,
        private UserAccessRevoker $accessRevoker,
    ) {}

    /**
     * @param  array{email: string, password: string, password_confirmation: string, token: string}  $credentials
     */
    public function handle(PasswordBroker $broker, array $credentials): string
    {
        $this->passwordPolicy->validate($credentials['password']);

        return $broker->reset($credentials, function (User $user, string $password): void {
            $user->forceFill([
                'password' => $password,
                'must_change_password' => false,
                'password_changed_at' => now(),
            ])->save();

            $this->accessRevoker->revokeAll((string) $user->getKey());
            event(new PasswordReset($user));
        });
    }
}
