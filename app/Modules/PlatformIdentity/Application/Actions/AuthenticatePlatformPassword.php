<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Application\Actions;

use App\Modules\PlatformIdentity\Domain\Enums\PlatformUserStatus;
use App\Modules\PlatformIdentity\Domain\Models\PlatformUser;
use Illuminate\Support\Facades\Hash;

final class AuthenticatePlatformPassword
{
    public function handle(string $email, string $password): ?PlatformUser
    {
        $user = PlatformUser::query()
            ->where('email', mb_strtolower(trim($email)))
            ->first();

        if (
            ! $user instanceof PlatformUser
            || $user->status === PlatformUserStatus::Suspended
            || ! Hash::check($password, $user->password)
        ) {
            return null;
        }

        if (Hash::needsRehash($user->password)) {
            $user->forceFill(['password' => Hash::make($password)])->save();
        }

        return $user;
    }
}
