<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Application\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

final class PlatformPasswordPolicy
{
    /**
     * @throws ValidationException
     */
    public function validate(string $password): void
    {
        $rule = Password::min((int) config('platform_identity.password.min', 12));

        if ((bool) config('platform_identity.password.check_compromised', true)) {
            $rule->uncompromised();
        }

        Validator::make(
            ['password' => $password],
            ['password' => ['required', 'string', 'max:'.(int) config('platform_identity.password.max', 128), $rule]],
        )->validate();
    }
}
