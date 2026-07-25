<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Modules\Identity\Domain\Models\User;
use App\Modules\PlatformIdentity\Domain\Enums\PlatformUserStatus;
use App\Modules\PlatformIdentity\Domain\Models\PlatformUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use OTPHP\InternalClock;
use OTPHP\TOTP;
use Tests\TestCase;

final class TenantProvisioningWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('identity.password.check_compromised', false);
    }

    public function test_platform_admin_must_recently_confirm_before_provisioning_from_web(): void
    {
        $platformUser = $this->platformUser();
        $this->passwordAndTotpLogin($platformUser);

        $this->get(route('platform.tenants.create'))
            ->assertRedirect(route('platform.confirm-sensitive'));

        $this->travel(31)->seconds();
        $this->post(route('platform.confirm-sensitive.store'), [
            'password' => 'correct horse battery staple',
            'code' => TOTP::createFromSecret('JBSWY3DPEHPK3PXP', new InternalClock)->now(),
        ])->assertRedirect(route('platform.tenants.create'));

        $this->get(route('platform.tenants.create'))
            ->assertOk()
            ->assertSee('Provision tenant')
            ->assertSee('Initial Tenant Owner');

        $response = $this->post(route('platform.tenants.store'), [
            'idempotency_key' => strtolower((string) Str::ulid()),
            'tenant_name' => 'Kopi Nusantara',
            'tenant_code' => 'kopi-nusantara',
            'currency' => 'IDR',
            'timezone' => 'Asia/Jakarta',
            'outlet_name' => 'Main Outlet',
            'outlet_code' => 'MAIN',
            'owner_name' => 'Tenant Owner',
            'owner_email' => 'owner@example.com',
            'password' => 'tenant owner secure password',
            'password_confirmation' => 'tenant owner secure password',
            'reason' => 'Pilot onboarding ticket PILOT-001',
        ]);

        $tenantId = (string) $this->app['db']->table('tenants')->value('id');
        $response->assertRedirect(route('platform.tenants.show', ['tenant' => $tenantId]));
        $this->assertDatabaseHas('tenants', ['id' => $tenantId, 'code' => 'kopi-nusantara']);
    }

    public function test_tenant_user_session_cannot_access_platform_provisioning_routes(): void
    {
        $tenantUser = User::factory()->create();

        $this->actingAs($tenantUser, 'web')
            ->get(route('platform.tenants.index'))
            ->assertRedirect(route('platform.login'));
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
