<?php

use App\Modules\Catalog\Infrastructure\Providers\CatalogServiceProvider;
use App\Modules\Identity\Infrastructure\Providers\IdentityServiceProvider;
use App\Modules\Inventory\Infrastructure\Providers\InventoryServiceProvider;
use App\Modules\PlatformIdentity\Infrastructure\Providers\PlatformIdentityServiceProvider;
use App\Modules\Procurement\Infrastructure\Providers\ProcurementServiceProvider;
use App\Modules\Recipe\Infrastructure\Providers\RecipeServiceProvider;
use App\Modules\Sales\Infrastructure\Providers\SalesServiceProvider;
use App\Modules\Tenancy\Infrastructure\Providers\TenancyServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    IdentityServiceProvider::class,
    PlatformIdentityServiceProvider::class,
    TenancyServiceProvider::class,
    CatalogServiceProvider::class,
    SalesServiceProvider::class,
    InventoryServiceProvider::class,
    RecipeServiceProvider::class,
    ProcurementServiceProvider::class,
];
