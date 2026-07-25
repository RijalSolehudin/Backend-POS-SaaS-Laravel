<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence;

use App\Modules\Identity\Application\Contracts\UserAccessRevoker;
use Illuminate\Support\Facades\DB;

final class DatabaseUserAccessRevoker implements UserAccessRevoker
{
    public function revokeAll(string $userId): void
    {
        DB::table('sessions')->where('user_id', $userId)->delete();
        DB::table('personal_access_tokens')
            ->where('tokenable_type', 'App\\Modules\\Identity\\Domain\\Models\\User')
            ->where('tokenable_id', $userId)
            ->delete();
    }
}
