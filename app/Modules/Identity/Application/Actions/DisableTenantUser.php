<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Actions;

use App\Modules\Identity\Application\Contracts\UserAccessRevoker;
use App\Modules\Identity\Domain\Enums\UserStatus;
use App\Modules\Identity\Domain\Models\User;

final readonly class DisableTenantUser
{
    public function __construct(private UserAccessRevoker $accessRevoker) {}

    public function handle(User $user): void
    {
        if ($user->status !== UserStatus::Disabled) {
            $user->forceFill(['status' => UserStatus::Disabled])->save();
        }

        $this->accessRevoker->revokeAll((string) $user->getKey());
    }
}
