<?php

use App\Modules\Identity\Infrastructure\Providers\IdentityServiceProvider;
use App\Modules\PlatformIdentity\Infrastructure\Providers\PlatformIdentityServiceProvider;
use App\Modules\Tenancy\Infrastructure\Providers\TenancyServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    IdentityServiceProvider::class,
    PlatformIdentityServiceProvider::class,
    TenancyServiceProvider::class,
];
