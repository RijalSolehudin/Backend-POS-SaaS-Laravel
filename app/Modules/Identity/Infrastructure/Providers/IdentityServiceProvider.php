<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Providers;

use App\Modules\Identity\Application\Actions\CreateInitialTenantOwner;
use App\Modules\Identity\Application\Contracts\InitialTenantOwnerCreator;
use Illuminate\Support\ServiceProvider;

final class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(base_path('config/identity.php'), 'identity');
        $this->app->bind(InitialTenantOwnerCreator::class, CreateInitialTenantOwner::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(dirname(__DIR__).'/Persistence/Migrations');
    }
}
