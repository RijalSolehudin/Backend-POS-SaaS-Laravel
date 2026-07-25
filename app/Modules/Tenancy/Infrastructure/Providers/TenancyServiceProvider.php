<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Infrastructure\Providers;

use App\Modules\Tenancy\Application\Contracts\TenancyAuditRecorder;
use App\Modules\Tenancy\Infrastructure\Audit\DatabaseTenancyAuditRecorder;
use App\Modules\Tenancy\Presentation\Console\Commands\ProvisionTenantCommand;
use Illuminate\Support\ServiceProvider;

final class TenancyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(base_path('config/tenancy.php'), 'tenancy');
        $this->app->bind(TenancyAuditRecorder::class, DatabaseTenancyAuditRecorder::class);
    }

    public function boot(): void
    {
        $moduleRoot = dirname(__DIR__, 2);

        $this->loadMigrationsFrom($moduleRoot.'/Infrastructure/Persistence/Migrations');
        $this->loadViewsFrom($moduleRoot.'/Presentation/Resources/views', 'tenancy');
        $this->loadRoutesFrom($moduleRoot.'/Presentation/Http/Routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ProvisionTenantCommand::class,
            ]);
        }
    }
}
