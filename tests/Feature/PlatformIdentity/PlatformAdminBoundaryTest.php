<?php

declare(strict_types=1);

namespace Tests\Feature\PlatformIdentity;

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

        self::assertNotNull($securityRoute);
        self::assertNotNull($revokeRoute);
        self::assertContains('platform.web', $securityRoute->middleware());
        self::assertContains('platform.authenticated', $securityRoute->middleware());
        self::assertContains('platform.confirmed', $revokeRoute->middleware());
    }
}
