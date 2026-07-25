<?php

declare(strict_types=1);

namespace Tests\Feature\PlatformIdentity;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class PlatformAdminBoundaryTest extends TestCase
{
    public function test_unknown_platform_html_route_uses_the_safe_error_boundary(): void
    {
        $this->get('/platform/not-a-real-page')
            ->assertNotFound()
            ->assertSee('Page not found')
            ->assertSee('Error 404')
            ->assertDontSee('NotFoundHttpException');
    }

    public function test_platform_routes_keep_the_explicit_authentication_and_confirmation_boundaries(): void
    {
        $securityRoute = Route::getRoutes()->getByName('platform.security');
        $revokeRoute = Route::getRoutes()->getByName('platform.sessions.revoke');
        $tenantCreateRoute = Route::getRoutes()->getByName('platform.tenants.create');
        $tenantStoreRoute = Route::getRoutes()->getByName('platform.tenants.store');
        $tenantDisableRoute = Route::getRoutes()->getByName('platform.tenants.disable');

        self::assertNotNull($securityRoute);
        self::assertNotNull($revokeRoute);
        self::assertNotNull($tenantCreateRoute);
        self::assertNotNull($tenantStoreRoute);
        self::assertNotNull($tenantDisableRoute);
        self::assertContains('platform.web', $securityRoute->middleware());
        self::assertContains('platform.authenticated', $securityRoute->middleware());
        self::assertContains('platform.confirmed', $revokeRoute->middleware());
        self::assertContains('platform.authenticated', $tenantStoreRoute->middleware());
        self::assertContains('platform.confirmed', $tenantCreateRoute->middleware());
        self::assertContains('platform.confirmed', $tenantStoreRoute->middleware());
        self::assertContains('platform.confirmed', $tenantDisableRoute->middleware());
    }

    public function test_controlled_provisioning_cli_rejects_non_interactive_execution(): void
    {
        $exitCode = Artisan::call('tenant:provision', ['--no-interaction' => true]);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString(
            'This command only supports interactive execution.',
            Artisan::output(),
        );
    }
}
