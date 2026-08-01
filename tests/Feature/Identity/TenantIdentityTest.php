<?php

declare(strict_types=1);

namespace Tests\Feature\Identity;

use App\Modules\Identity\Domain\Enums\UserStatus;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\PlatformIdentity\Domain\Enums\PlatformUserStatus;
use App\Modules\PlatformIdentity\Domain\Models\PlatformUser;
use App\Modules\Tenancy\Domain\Enums\MembershipType;
use App\Modules\Tenancy\Domain\Enums\TenantStatus;
use App\Modules\Tenancy\Domain\Models\Tenant;
use App\Modules\Tenancy\Domain\Models\TenantMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

final class TenantIdentityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('identity.password.check_compromised', false);
        config()->set('session.cookie', 'pos_tenant_session');
    }

    public function test_active_tenant_user_signs_in_without_submitting_a_tenant_identifier(): void
    {
        [$user, $tenant] = $this->tenantUser();

        $response = $this->post(route('tenant.login.store'), [
            'email' => strtoupper($user->email),
            'password' => 'initial tenant password',
        ]);

        $response->assertRedirect(route('tenant.home', ['tenant' => $tenant->id]));
        $response->assertCookie('pos_tenant_session');
        $response->assertCookieMissing('pos_platform_session');
        $this->assertAuthenticatedAs($user, 'web');
        $this->get(route('tenant.home', ['tenant' => $tenant->id]))
            ->assertOk()
            ->assertSee($tenant->name);
    }

    public function test_initial_owner_must_change_password_before_entering_tenant_admin(): void
    {
        [$user, $tenant] = $this->tenantUser(mustChangePassword: true);

        $this->post(route('tenant.login.store'), [
            'email' => $user->email,
            'password' => 'initial tenant password',
        ])->assertRedirect(route('tenant.password.change', ['tenant' => $tenant->id]));

        $this->get(route('tenant.home', ['tenant' => $tenant->id]))
            ->assertRedirect(route('tenant.password.change', ['tenant' => $tenant->id]));

        $this->put(route('tenant.password.update', ['tenant' => $tenant->id]), [
            'current_password' => 'initial tenant password',
            'password' => 'replacement tenant password',
            'password_confirmation' => 'replacement tenant password',
        ])->assertRedirect(route('tenant.home', ['tenant' => $tenant->id]));

        $this->assertFalse($user->refresh()->must_change_password);
        $this->assertTrue(Hash::check('replacement tenant password', $user->password));
    }

    public function test_platform_credentials_never_authenticate_a_tenant_session(): void
    {
        PlatformUser::query()->create([
            'name' => 'Platform Admin',
            'email' => 'platform@example.com',
            'password' => Hash::make('platform password'),
            'status' => PlatformUserStatus::Active,
            'password_changed_at' => now(),
        ]);

        $this->post(route('tenant.login.store'), [
            'email' => 'platform@example.com',
            'password' => 'platform password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('web');
    }

    public function test_cross_tenant_route_is_hidden_and_disabled_tenant_is_revoked_on_next_request(): void
    {
        [$user, $tenant] = $this->tenantUser();
        [, $otherTenant] = $this->tenantUser(email: 'other@example.com');

        $this->login($user);

        $this->get(route('tenant.home', ['tenant' => $otherTenant->id]))->assertNotFound();
        $this->assertAuthenticatedAs($user, 'web');

        $tenant->forceFill(['status' => TenantStatus::Disabled])->save();

        $this->get(route('tenant.home', ['tenant' => $tenant->id]))
            ->assertRedirect(route('tenant.login'));
        $this->assertGuest('web');
    }

    public function test_disabled_user_is_logged_out_on_the_next_request(): void
    {
        [$user, $tenant] = $this->tenantUser();
        $this->login($user);
        $user->forceFill(['status' => UserStatus::Disabled])->save();

        $this->get(route('tenant.home', ['tenant' => $tenant->id]))
            ->assertRedirect(route('tenant.login'));
        $this->assertGuest('web');
    }

    public function test_idle_and_absolute_limits_are_enforced_server_side(): void
    {
        [$user, $tenant] = $this->tenantUser();
        $this->login($user, authenticatedAt: now()->subHours(9)->getTimestamp());

        $this->get(route('tenant.home', ['tenant' => $tenant->id]))
            ->assertRedirect(route('tenant.login'));
        $this->assertGuest('web');
    }

    public function test_passive_session_status_does_not_extend_idle_timeout(): void
    {
        [$user, $tenant] = $this->tenantUser();
        $this->login($user);

        $this->travel(29)->minutes();
        $this->get(route('tenant.session.status', ['tenant' => $tenant->id]))->assertNoContent();

        $this->travel(2)->minutes();
        $this->get(route('tenant.home', ['tenant' => $tenant->id]))
            ->assertRedirect(route('tenant.login'));
    }

    public function test_password_reset_revokes_every_database_session_and_sanctum_token(): void
    {
        [$user] = $this->tenantUser();
        $user->createToken('POS device');
        $this->app['db']->table('sessions')->insert([
            'id' => 'tenant-session-one',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => '',
            'last_activity' => now()->getTimestamp(),
        ]);
        $token = null;
        Password::broker('users')->sendResetLink(['email' => $user->email], function (User $resetUser, string $resetToken) use (&$token): void {
            $token = $resetToken;
        });
        $this->assertIsString($token);

        $this->post(route('tenant.password.reset.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'reset tenant password',
            'password_confirmation' => 'reset tenant password',
        ])->assertRedirect(route('tenant.login'));

        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
        $this->assertTrue(Hash::check('reset tenant password', $user->refresh()->password));
    }

    /**
     * @return array{User, Tenant}
     */
    private function tenantUser(
        bool $mustChangePassword = false,
        string $email = 'owner@example.com',
    ): array {
        $user = User::factory()->create([
            'email' => $email,
            'password' => 'initial tenant password',
            'status' => UserStatus::Active,
            'must_change_password' => $mustChangePassword,
        ]);
        $tenant = Tenant::query()->create([
            'name' => 'Kopi Nusantara',
            'code' => 'tenant-'.substr($user->id, -8),
            'currency' => 'IDR',
            'timezone' => 'Asia/Jakarta',
            'status' => TenantStatus::Active,
        ]);
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'membership_type' => MembershipType::Owner,
        ]);

        return [$user, $tenant];
    }

    private function login(User $user, ?int $authenticatedAt = null): void
    {
        $this->actingAs($user, 'web')
            ->withSession([
                'tenant.authenticated_at' => $authenticatedAt ?? now()->getTimestamp(),
                'tenant.last_activity_at' => now()->getTimestamp(),
            ]);
    }
}
