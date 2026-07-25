<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

final class TenantPasswordPolicy
{
    /**
     * @throws ValidationException
     */
    public function validate(string $password): void
    {
        $rule = Password::min((int) config('identity.password.min', 12));

        if ((bool) config('identity.password.check_compromised', true)) {
            $rule->uncompromised();
        }

        Validator::make(
            ['password' => $password],
            ['password' => ['required', 'string', 'max:'.(int) config('identity.password.max', 128), $rule]],
        )->validate();
    }
}
