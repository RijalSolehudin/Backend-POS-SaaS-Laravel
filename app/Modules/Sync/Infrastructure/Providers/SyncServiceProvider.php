<?php

declare(strict_types=1);

namespace App\Modules\Sync\Infrastructure\Providers;

use App\Modules\Sync\Presentation\Console\Commands\SyncPerformanceBaselineCommand;
use Illuminate\Support\ServiceProvider;

final class SyncServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(base_path('config/sync.php'), 'sync');
    }

    public function boot(): void
    {
        $moduleRoot = dirname(__DIR__, 2);

        $this->loadMigrationsFrom($moduleRoot.'/Infrastructure/Persistence/Migrations');
        $this->loadRoutesFrom($moduleRoot.'/Presentation/Http/Routes/api.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncPerformanceBaselineCommand::class,
            ]);
        }
    }
}
