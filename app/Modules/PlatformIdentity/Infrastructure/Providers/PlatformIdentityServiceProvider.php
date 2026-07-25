<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Infrastructure\Providers;

use App\Modules\PlatformIdentity\Application\Contracts\PlatformSessionRepository;
use App\Modules\PlatformIdentity\Application\Contracts\QrCodeRenderer;
use App\Modules\PlatformIdentity\Application\Contracts\RecoveryCodeGenerator;
use App\Modules\PlatformIdentity\Application\Contracts\SecurityAuditRecorder;
use App\Modules\PlatformIdentity\Application\Contracts\TotpAuthenticator;
use App\Modules\PlatformIdentity\Infrastructure\Audit\DatabaseSecurityAuditRecorder;
use App\Modules\PlatformIdentity\Infrastructure\Persistence\DatabasePlatformSessionRepository;
use App\Modules\PlatformIdentity\Infrastructure\Security\BaconQrCodeRenderer;
use App\Modules\PlatformIdentity\Infrastructure\Security\OtphpTotpAuthenticator;
use App\Modules\PlatformIdentity\Infrastructure\Security\RandomRecoveryCodeGenerator;
use App\Modules\PlatformIdentity\Infrastructure\Session\PlatformSessionManager;
use App\Modules\PlatformIdentity\Presentation\Console\Commands\BootstrapPlatformAdministratorCommand;
use App\Modules\PlatformIdentity\Presentation\Console\Commands\PrunePlatformSecurityStateCommand;
use App\Modules\PlatformIdentity\Presentation\Console\Commands\RecoverPlatformAccessCommand;
use App\Modules\PlatformIdentity\Presentation\Http\Middleware\EnforcePlatformSessionPolicy;
use App\Modules\PlatformIdentity\Presentation\Http\Middleware\PreventPlatformResponseCaching;
use App\Modules\PlatformIdentity\Presentation\Http\Middleware\RequireRecentPlatformConfirmation;
use App\Modules\PlatformIdentity\Presentation\Http\Middleware\StartPlatformSession;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Cookie\QueueingFactory as CookieJar;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Router;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Psr\Clock\ClockInterface;

final class PlatformIdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            base_path('config/platform_identity.php'),
            'platform_identity',
        );

        $this->app->singleton(PlatformSessionManager::class);
        $this->app
            ->when(StartPlatformSession::class)
            ->needs(SessionManager::class)
            ->give(PlatformSessionManager::class);

        $this->app->bind(
            TotpAuthenticator::class,
            fn (): TotpAuthenticator => new OtphpTotpAuthenticator(
                OtphpTotpAuthenticator::clock(),
                (string) config('platform_identity.issuer'),
            ),
        );
        $this->app->bind(QrCodeRenderer::class, BaconQrCodeRenderer::class);
        $this->app->bind(RecoveryCodeGenerator::class, RandomRecoveryCodeGenerator::class);
        $this->app->bind(PlatformSessionRepository::class, DatabasePlatformSessionRepository::class);
        $this->app->bind(SecurityAuditRecorder::class, DatabaseSecurityAuditRecorder::class);
        $this->app->bind(ClockInterface::class, fn (): ClockInterface => OtphpTotpAuthenticator::clock());
    }

    public function boot(Router $router): void
    {
        $moduleRoot = dirname(__DIR__, 2);

        $this->loadMigrationsFrom($moduleRoot.'/Infrastructure/Persistence/Migrations');
        $this->loadViewsFrom($moduleRoot.'/Presentation/Resources/views', 'platform-identity');

        $this->registerPlatformGuard();
        $this->registerMiddleware($router);
        $this->loadRoutesFrom($moduleRoot.'/Presentation/Http/Routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                BootstrapPlatformAdministratorCommand::class,
                RecoverPlatformAccessCommand::class,
                PrunePlatformSecurityStateCommand::class,
            ]);

            Schedule::command('platform:prune-security-state')
                ->hourly()
                ->withoutOverlapping();
        }
    }

    private function registerPlatformGuard(): void
    {
        Auth::extend('platform-session', function (Application $app, string $name, array $config): SessionGuard {
            $auth = Auth::getFacadeRoot();
            $provider = $auth->createUserProvider($config['provider'] ?? null);
            $configuration = $app->make(ConfigRepository::class);

            $guard = new SessionGuard(
                $name,
                $provider,
                $app->make(PlatformSessionManager::class)->driver(),
                rehashOnLogin: (bool) $configuration->get('hashing.rehash_on_login', true),
                timeboxDuration: (int) $configuration->get('auth.timebox_duration', 200000),
                hashKey: (string) $configuration->get('app.key'),
            );

            $guard->setCookieJar($app->make(CookieJar::class));
            $guard->setDispatcher($app->make(Dispatcher::class));
            $guard->setRequest($app->make(Request::class));

            return $guard;
        });
    }

    private function registerMiddleware(Router $router): void
    {
        $router->middlewareGroup('platform.web', [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartPlatformSession::class,
            ShareErrorsFromSession::class,
            ValidateCsrfToken::class,
            SubstituteBindings::class,
            PreventPlatformResponseCaching::class,
        ]);

        $router->aliasMiddleware('platform.session-policy', EnforcePlatformSessionPolicy::class);
        $router->aliasMiddleware('platform.confirmed', RequireRecentPlatformConfirmation::class);
        $router->middlewareGroup('platform.authenticated', [
            'auth:platform',
            'platform.session-policy',
        ]);
    }
}
