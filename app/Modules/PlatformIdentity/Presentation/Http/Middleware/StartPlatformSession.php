<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Presentation\Http\Middleware;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Session\SessionManager;
use Illuminate\Session\Store;

final class StartPlatformSession extends StartSession
{
    public function __construct(SessionManager $manager, CacheFactory $cache)
    {
        parent::__construct($manager, fn (): CacheFactory => $cache);
    }

    public function getSession(Request $request): Session
    {
        $session = parent::getSession($request);

        if ($session instanceof Store) {
            app(Redirector::class)->setSession($session);
        }

        return $session;
    }
}
