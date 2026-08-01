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

final class PlatformAdminShellTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_uses_the_shared_guest_shell_with_accessible_form_feedback(): void
    {
        $this->get(route('platform.login'))
            ->assertOk()
            ->assertSee('Protected workspace')
            ->assertSee('Sign in to Platform Admin')
            ->assertSee('Platform access is separate from every tenant account.');

        $this->from(route('platform.login'))
            ->post(route('platform.login.store'), [])
            ->assertRedirect(route('platform.login'));

        $this->followingRedirects()
            ->from(route('platform.login'))
            ->post(route('platform.login.store'), [])
            ->assertSee('role="alert"', false)
            ->assertSee('aria-live="polite"', false);
    }

    public function test_platform_home_requires_the_platform_guard_and_redirects_to_the_current_overview(): void
    {
        $this->get(route('platform.home'))
            ->assertRedirect(route('platform.login'));

        $user = $this->platformUser();
        $this->passwordAndTotpLogin($user);

        $this->get(route('platform.home'))
            ->assertRedirect(route('platform.security'));
    }

    public function test_authenticated_shell_exposes_security_navigation_and_session_state(): void
    {
        $user = $this->platformUser();
        $this->passwordAndTotpLogin($user);

        $this->get(route('platform.security'))
            ->assertOk()
            ->assertSee('POS Platform')
            ->assertSee('Account security')
            ->assertSee('Active platform sessions')
            ->assertSee($user->email)
            ->assertSee('aria-label="Platform navigation"', false)
            ->assertSee('x-data=', false);
    }

    private function passwordAndTotpLogin(PlatformUser $user): void
    {
        $this->post(route('platform.login.store'), [
            'email' => $user->email,
            'password' => 'correct horse battery staple',
        ])->assertRedirect(route('platform.mfa.challenge'));

        $this->post(route('platform.mfa.challenge.store'), [
            'code' => TOTP::createFromSecret('JBSWY3DPEHPK3PXP', new InternalClock)->now(),
        ])->assertRedirect(route('platform.security'));
    }

    private function platformUser(): PlatformUser
    {
        return PlatformUser::query()->create([
            'name' => 'Platform Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('correct horse battery staple'),
            'status' => PlatformUserStatus::Active,
            'totp_secret' => 'JBSWY3DPEHPK3PXP',
            'totp_confirmed_at' => now(),
            'password_changed_at' => now(),
        ]);
    }
}
