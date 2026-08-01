<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Infrastructure\Providers;

use App\Modules\Identity\Application\Contracts\TenantAccessResolver;
use App\Modules\Tenancy\Application\Contracts\TenancyAuditRecorder;
use App\Modules\Tenancy\Application\Contracts\TenantCatalogReference;
use App\Modules\Tenancy\Infrastructure\Audit\DatabaseTenancyAuditRecorder;
use App\Modules\Tenancy\Infrastructure\Catalog\DatabaseTenantCatalogReference;
use App\Modules\Tenancy\Infrastructure\Identity\DatabaseTenantAccessResolver;
use App\Modules\Tenancy\Presentation\Console\Commands\ProvisionTenantCommand;
use App\Modules\Tenancy\Presentation\Http\Middleware\RequireTenantOwner;
use App\Modules\Tenancy\Presentation\Http\Middleware\ResolveTenantContext;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

final class TenancyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(base_path('config/tenancy.php'), 'tenancy');
        $this->app->bind(TenantCatalogReference::class, DatabaseTenantCatalogReference::class);
        $this->app->bind(TenancyAuditRecorder::class, DatabaseTenancyAuditRecorder::class);
        $this->app->bind(TenantAccessResolver::class, DatabaseTenantAccessResolver::class);
    }

    public function boot(Router $router): void
    {
        $moduleRoot = dirname(__DIR__, 2);

        $this->loadMigrationsFrom($moduleRoot.'/Infrastructure/Persistence/Migrations');
        $this->loadViewsFrom($moduleRoot.'/Presentation/Resources/views', 'tenancy');
        $router->aliasMiddleware('tenant.context', ResolveTenantContext::class);
        $router->aliasMiddleware('tenant.owner', RequireTenantOwner::class);
        $this->loadRoutesFrom($moduleRoot.'/Presentation/Http/Routes/web.php');
        $this->loadRoutesFrom($moduleRoot.'/Presentation/Http/Routes/api.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ProvisionTenantCommand::class,
            ]);
        }
    }
}
