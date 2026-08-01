<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Actions;

use Laravel\Sanctum\PersonalAccessToken;

final readonly class RevokeCurrentPosToken
{
    public function handle(PersonalAccessToken $token): void
    {
        $token->delete();
    }
}
