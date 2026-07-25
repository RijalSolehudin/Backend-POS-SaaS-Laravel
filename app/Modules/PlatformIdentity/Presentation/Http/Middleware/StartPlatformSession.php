<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Presentation\Http\Middleware;

use Illuminate\Session\Middleware\StartSession;

final class StartPlatformSession extends StartSession {}
