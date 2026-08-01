<?php

declare(strict_types=1);

namespace App\Modules\Sales\Infrastructure\Providers;

use App\Modules\Sales\Presentation\Console\Commands\PruneSalesAuditEventsCommand;
use App\Modules\Sales\Presentation\Console\Commands\SalesRecoveryCheckCommand;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\ServiceProvider;

final class SalesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(base_path('config/sales.php'), 'sales');
    }

    public function boot(): void
    {
        $moduleRoot = dirname(__DIR__, 2);

        $this->loadMigrationsFrom($moduleRoot.'/Infrastructure/Persistence/Migrations');
        $this->loadViewsFrom($moduleRoot.'/Presentation/Resources/views', 'sales');
        $this->loadRoutesFrom($moduleRoot.'/Presentation/Http/Routes/api.php');
        $this->loadRoutesFrom($moduleRoot.'/Presentation/Http/Routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                PruneSalesAuditEventsCommand::class,
                SalesRecoveryCheckCommand::class,
            ]);

            Schedule::command('sales:prune-audit-events')
                ->daily()
                ->withoutOverlapping();
        }
    }
}
