<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

final class CatalogServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $moduleRoot = dirname(__DIR__, 2);

        $this->loadMigrationsFrom($moduleRoot.'/Infrastructure/Persistence/Migrations');
        $this->loadViewsFrom($moduleRoot.'/Presentation/Resources/views', 'catalog');
        $this->loadRoutesFrom($moduleRoot.'/Presentation/Http/Routes/web.php');
        $this->loadRoutesFrom($moduleRoot.'/Presentation/Http/Routes/api.php');
    }
}
