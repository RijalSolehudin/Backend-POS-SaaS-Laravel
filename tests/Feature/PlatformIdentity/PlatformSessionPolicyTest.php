<?php

declare(strict_types=1);

namespace Tests\Feature\PlatformIdentity;

use App\Modules\PlatformIdentity\Domain\Enums\PlatformUserStatus;
use App\Modules\PlatformIdentity\Domain\Models\PlatformUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use OTPHP\InternalClock;
use OTPHP\TOTP;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

final class PlatformSessionPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_third_login_must_select_an_existing_session_to_revoke(): void
    {
        $user = $this->platformUser();

        foreach (['existing-one', 'existing-two'] as $sessionId) {
            DB::table('platform_sessions')->insert([
                'id' => $sessionId,
                'user_id' => $user->getKey(),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Test browser',
                'payload' => 'encrypted-payload',
                'last_activity' => now()->getTimestamp(),
                'created_at' => now(),
            ]);
        }

        $this->passwordAndTotpLogin($user)
            ->assertRedirect(route('platform.session-replacement'));

        $this->get(route('platform.session-replacement'))
            ->assertOk()
            ->assertSee('Two active sessions already exist');
        $this->assertGuest('platform');
    }

    public function test_absolute_timeout_is_enforced_even_when_idle_window_is_longer(): void
    {
        config()->set('platform_identity.session.idle_minutes', 300);
        $user = $this->platformUser();
        $this->passwordAndTotpLogin($user)->assertRedirect(route('platform.security'));

        $this->travel(241)->minutes();

        $this->get(route('platform.security'))
            ->assertRedirect(route('platform.login').'#session-expired');
        $this->assertGuest('platform');
    }

    public function test_suspended_identity_is_rejected_on_the_next_request(): void
    {
        $user = $this->platformUser();
        $this->passwordAndTotpLogin($user)->assertRedirect(route('platform.security'));

        $user->forceFill(['status' => PlatformUserStatus::Suspended])->save();

        $this->get(route('platform.security'))->assertRedirect(route('platform.login'));
        $this->assertGuest('platform');
    }

    /**
     * @return TestResponse<Response>
     */
    private function passwordAndTotpLogin(PlatformUser $user): TestResponse
    {
        $this->post(route('platform.login.store'), [
            'email' => $user->email,
            'password' => 'correct horse battery staple',
        ])->assertRedirect(route('platform.mfa.challenge'));

        return $this->post(route('platform.mfa.challenge.store'), [
            'code' => TOTP::createFromSecret('JBSWY3DPEHPK3PXP', new InternalClock)->now(),
        ]);
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
