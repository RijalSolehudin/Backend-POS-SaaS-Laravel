<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Infrastructure\Session;

use Illuminate\Session\DatabaseSessionHandler;
use Illuminate\Support\Facades\Auth;

final class PlatformDatabaseSessionHandler extends DatabaseSessionHandler
{
    protected function userId(): mixed
    {
        return Auth::guard('platform')->id();
    }
}
