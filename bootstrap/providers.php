<?php

use App\Modules\PlatformIdentity\Infrastructure\Providers\PlatformIdentityServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    PlatformIdentityServiceProvider::class,
];
