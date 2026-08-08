<?php

declare(strict_types=1);

namespace App\Modules\Dining\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

final class DiningServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $moduleRoot = dirname(__DIR__, 2);

        $this->loadMigrationsFrom($moduleRoot.'/Infrastructure/Persistence/Migrations');
        $this->loadViewsFrom($moduleRoot.'/Presentation/Resources/views', 'dining');
        $this->loadRoutesFrom($moduleRoot.'/Presentation/Http/Routes/web.php');
    }
}
