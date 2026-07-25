<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Presentation\Http\Controllers;

use Illuminate\Http\RedirectResponse;

final class PlatformHomeController
{
    public function __invoke(): RedirectResponse
    {
        return new RedirectResponse(route('platform.security'));
    }
}
