<?php

declare(strict_types=1);

namespace App\Modules\Sales\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

final class SalesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $moduleRoot = dirname(__DIR__, 2);

        $this->loadMigrationsFrom($moduleRoot.'/Infrastructure/Persistence/Migrations');
        $this->loadViewsFrom($moduleRoot.'/Presentation/Resources/views', 'sales');
        $this->loadRoutesFrom($moduleRoot.'/Presentation/Http/Routes/api.php');
        $this->loadRoutesFrom($moduleRoot.'/Presentation/Http/Routes/web.php');
    }
}
