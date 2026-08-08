<?php

use App\Modules\Catalog\Infrastructure\Providers\CatalogServiceProvider;
use App\Modules\Dining\Infrastructure\Providers\DiningServiceProvider;
use App\Modules\Identity\Infrastructure\Providers\IdentityServiceProvider;
use App\Modules\Inventory\Infrastructure\Providers\InventoryServiceProvider;
use App\Modules\Kitchen\Infrastructure\Providers\KitchenServiceProvider;
use App\Modules\OrderingChannel\Infrastructure\Providers\OrderingChannelServiceProvider;
use App\Modules\PaymentsGateway\Infrastructure\Providers\PaymentsGatewayServiceProvider;
use App\Modules\PlatformIdentity\Infrastructure\Providers\PlatformIdentityServiceProvider;
use App\Modules\Procurement\Infrastructure\Providers\ProcurementServiceProvider;
use App\Modules\Promotion\Infrastructure\Providers\PromotionServiceProvider;
use App\Modules\Recipe\Infrastructure\Providers\RecipeServiceProvider;
use App\Modules\Reporting\Infrastructure\Providers\ReportingServiceProvider;
use App\Modules\Reservation\Infrastructure\Providers\ReservationServiceProvider;
use App\Modules\Sales\Infrastructure\Providers\SalesServiceProvider;
use App\Modules\Sync\Infrastructure\Providers\SyncServiceProvider;
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
    DiningServiceProvider::class,
    KitchenServiceProvider::class,
    OrderingChannelServiceProvider::class,
    PaymentsGatewayServiceProvider::class,
    ReservationServiceProvider::class,
    PromotionServiceProvider::class,
    ReportingServiceProvider::class,
    SyncServiceProvider::class,
];
