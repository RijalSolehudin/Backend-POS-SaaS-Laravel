<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Providers;

use App\Modules\Identity\Application\Actions\CreateInitialTenantOwner;
use App\Modules\Identity\Application\Contracts\InitialTenantOwnerCreator;
use App\Modules\Identity\Application\Contracts\UserAccessRevoker;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Identity\Infrastructure\Persistence\DatabaseUserAccessRevoker;
use App\Modules\Identity\Presentation\Http\Middleware\EnforceTenantSessionPolicy;
use App\Modules\Identity\Presentation\Http\Middleware\RequireCurrentTenantPassword;
use App\Modules\Identity\Presentation\Http\Middleware\ResolveTenantContext;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

final class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(base_path('config/identity.php'), 'identity');
        $this->app->bind(InitialTenantOwnerCreator::class, CreateInitialTenantOwner::class);
        $this->app->bind(UserAccessRevoker::class, DatabaseUserAccessRevoker::class);
    }

    public function boot(Router $router): void
    {
        $moduleRoot = dirname(__DIR__, 2);

        $this->loadMigrationsFrom($moduleRoot.'/Infrastructure/Persistence/Migrations');
        $this->loadViewsFrom($moduleRoot.'/Presentation/Resources/views', 'identity');
        $router->aliasMiddleware('tenant.session-policy', EnforceTenantSessionPolicy::class);
        $router->aliasMiddleware('tenant.password-current', RequireCurrentTenantPassword::class);
        $router->aliasMiddleware('tenant.context', ResolveTenantContext::class);
        $router->middlewareGroup('tenant.authenticated', [
            'auth:web',
            'tenant.session-policy',
        ]);
        $this->loadRoutesFrom($moduleRoot.'/Presentation/Http/Routes/web.php');

        ResetPassword::createUrlUsing(
            fn (User $user, string $token): string => route('tenant.password.reset', [
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ]),
        );
    }
}
