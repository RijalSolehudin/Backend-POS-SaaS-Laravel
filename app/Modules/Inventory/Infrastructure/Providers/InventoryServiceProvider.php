<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Infrastructure\Providers;

use App\Modules\Inventory\Presentation\Console\Commands\InventoryRecoveryCheckCommand;
use Illuminate\Support\ServiceProvider;

final class InventoryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $moduleRoot = dirname(__DIR__, 2);

        $this->loadMigrationsFrom($moduleRoot.'/Infrastructure/Persistence/Migrations');
        $this->loadViewsFrom($moduleRoot.'/Presentation/Resources/views', 'inventory');
        $this->loadRoutesFrom($moduleRoot.'/Presentation/Http/Routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InventoryRecoveryCheckCommand::class,
            ]);
        }
    }
}
