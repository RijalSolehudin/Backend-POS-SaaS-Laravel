<?php

declare(strict_types=1);

namespace Tests\Feature\PlatformIdentity;

use App\Modules\PlatformIdentity\Domain\Enums\PlatformUserStatus;
use App\Modules\PlatformIdentity\Domain\Models\PlatformUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use OTPHP\InternalClock;
use OTPHP\TOTP;
use Tests\TestCase;

final class PlatformAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_login_requires_password_then_totp_and_uses_an_isolated_cookie(): void
    {
        $secret = 'JBSWY3DPEHPK3PXP';
        $user = PlatformUser::query()->create([
            'name' => 'Platform Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('correct horse battery staple'),
            'status' => PlatformUserStatus::Active,
            'totp_secret' => $secret,
            'totp_confirmed_at' => now(),
            'password_changed_at' => now(),
        ]);

        $passwordResponse = $this->post(route('platform.login.store'), [
            'email' => 'ADMIN@example.com',
            'password' => 'correct horse battery staple',
        ]);

        $passwordResponse->assertRedirect(route('platform.mfa.challenge'));
        $this->assertGuest('platform');

        $mfaResponse = $this->post(route('platform.mfa.challenge.store'), [
            'code' => TOTP::createFromSecret($secret, new InternalClock)->now(),
        ]);

        $mfaResponse->assertRedirect(route('platform.security'));
        $authenticatedUser = PlatformUser::query()->whereKey($user->getKey())->firstOrFail();
        $this->assertAuthenticatedAs($authenticatedUser, 'platform');
        $mfaResponse->assertCookie((string) config('platform_identity.session.cookie'));
        $mfaResponse->assertCookieMissing((string) config('session.cookie'));
    }

    public function test_tenant_credentials_do_not_authenticate_on_platform_login(): void
    {
        $this->followingRedirects()
            ->from(route('platform.login'))
            ->post(route('platform.login.store'), [
                'email' => 'tenant@example.com',
                'password' => 'tenant-password',
            ])
            ->assertSee('The supplied credentials are invalid.');

        $this->assertGuest('platform');
    }
}
