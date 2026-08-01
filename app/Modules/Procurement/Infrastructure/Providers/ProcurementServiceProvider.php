<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

final class ProcurementServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Persistence/Migrations');
        $this->loadViewsFrom(__DIR__.'/../../Presentation/Resources/views', 'procurement');
        $this->loadRoutesFrom(__DIR__.'/../../Presentation/Http/Routes/web.php');
    }
}
