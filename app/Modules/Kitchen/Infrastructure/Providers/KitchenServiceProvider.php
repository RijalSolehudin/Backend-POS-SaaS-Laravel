<?php

declare(strict_types=1);

namespace App\Modules\Kitchen\Infrastructure\Providers;

use App\Modules\Kitchen\Application\Contracts\KitchenPrinterDispatcher;
use App\Modules\Kitchen\Infrastructure\Printing\NullKitchenPrinterDispatcher;
use Illuminate\Support\ServiceProvider;

final class KitchenServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(KitchenPrinterDispatcher::class, NullKitchenPrinterDispatcher::class);
    }

    public function boot(): void
    {
        $moduleRoot = dirname(__DIR__, 2);

        $this->loadMigrationsFrom($moduleRoot.'/Infrastructure/Persistence/Migrations');
        $this->loadRoutesFrom($moduleRoot.'/Presentation/Http/Routes/api.php');
    }
}
