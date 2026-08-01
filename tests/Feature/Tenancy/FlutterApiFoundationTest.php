<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Modules\Identity\Domain\Enums\PredefinedRole;
use App\Modules\Identity\Domain\Enums\UserStatus;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Identity\Domain\Models\UserRoleAssignment;
use App\Modules\Tenancy\Domain\Enums\MembershipType;
use App\Modules\Tenancy\Domain\Enums\OutletStatus;
use App\Modules\Tenancy\Domain\Enums\PosDeviceStatus;
use App\Modules\Tenancy\Domain\Enums\TenantStatus;
use App\Modules\Tenancy\Domain\Models\Outlet;
use App\Modules\Tenancy\Domain\Models\OutletUserAssignment;
use App\Modules\Tenancy\Domain\Models\PosDevice;
use App\Modules\Tenancy\Domain\Models\Tenant;
use App\Modules\Tenancy\Domain\Models\TenantMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

final class FlutterApiFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_login_issues_a_device_bound_sanctum_token_for_assigned_cashier(): void
    {
        [$cashier, $tenant] = $this->tenantUser(MembershipType::Member, 'cashier@example.com');
        $outlet = $this->outlet($tenant, 'MAIN');
        $device = $this->device($tenant, $outlet, '01k123456789abcdefghjkmnpq', $cashier);
        $this->role($cashier, PredefinedRole::Cashier);
        $this->assignOutlet($tenant, $outlet, $cashier);

        $response = $this->postJson(route('api.v1.pos.auth.login'), [
            'email' => 'cashier@example.com',
            'password' => 'password',
            'installation_id' => '01k123456789abcdefghjkmnpq',
            'outlet_id' => $outlet->id,
        ]);

        $response
            ->assertOk()
            ->assertHeader('X-Request-ID')
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.tenant_id', $tenant->id)
            ->assertJsonPath('data.outlet_id', $outlet->id)
            ->assertJsonPath('data.device_id', $device->id)
            ->assertJsonStructure(['data' => ['access_token', 'expires_at']]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $cashier->id,
            'pos_device_id' => $device->id,
            'name' => 'POS device',
        ]);
    }

    public function test_pos_login_replaces_the_previous_token_for_the_same_user_device_pair(): void
    {
        [$cashier, $tenant] = $this->tenantUser(MembershipType::Member, 'cashier@example.com');
        $outlet = $this->outlet($tenant, 'MAIN');
        $device = $this->device($tenant, $outlet, '01k123456789abcdefghjkmnpq', $cashier);
        $this->role($cashier, PredefinedRole::Cashier);
        $this->assignOutlet($tenant, $outlet, $cashier);

        $this->postJson(route('api.v1.pos.auth.login'), [
            'email' => 'cashier@example.com',
            'password' => 'password',
            'installation_id' => '01k123456789abcdefghjkmnpq',
            'outlet_id' => $outlet->id,
        ])->assertOk();

        $this->postJson(route('api.v1.pos.auth.login'), [
            'email' => 'cashier@example.com',
            'password' => 'password',
            'installation_id' => '01k123456789abcdefghjkmnpq',
            'outlet_id' => $outlet->id,
        ])->assertOk();

        self::assertSame(1, DB::table('personal_access_tokens')
            ->where('tokenable_id', $cashier->id)
            ->where('pos_device_id', $device->id)
            ->count());
    }

    public function test_pos_token_can_read_only_its_bound_outlet_context(): void
    {
        [$cashier, $tenant] = $this->tenantUser(MembershipType::Member, 'cashier@example.com');
        $outlet = $this->outlet($tenant, 'MAIN');
        $otherOutlet = $this->outlet($tenant, 'OTHER');
        $device = $this->device($tenant, $outlet, '01k123456789abcdefghjkmnpq', $cashier);
        $this->role($cashier, PredefinedRole::Cashier);
        $this->assignOutlet($tenant, $outlet, $cashier);

        $token = $this->loginToken($outlet);

        $this->withToken($token)
            ->getJson(route('api.v1.pos.outlets.context', ['outlet' => $outlet->id]))
            ->assertOk()
            ->assertJsonPath('data.tenant_id', $tenant->id)
            ->assertJsonPath('data.outlet_id', $outlet->id)
            ->assertJsonPath('data.device_id', $device->id)
            ->assertJsonPath('data.user_id', $cashier->id);

        $this->withToken($token)
            ->getJson(route('api.v1.pos.outlets.context', ['outlet' => $otherOutlet->id]))
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('code', 'OUTLET_NOT_FOUND');
    }

    public function test_unassigned_cashier_cannot_issue_pos_token(): void
    {
        [$cashier, $tenant] = $this->tenantUser(MembershipType::Member, 'cashier@example.com');
        $outlet = $this->outlet($tenant, 'MAIN');
        $this->device($tenant, $outlet, '01k123456789abcdefghjkmnpq', $cashier);
        $this->role($cashier, PredefinedRole::Cashier);

        $this->postJson(route('api.v1.pos.auth.login'), [
            'email' => 'cashier@example.com',
            'password' => 'password',
            'installation_id' => '01k123456789abcdefghjkmnpq',
            'outlet_id' => $outlet->id,
        ])
            ->assertForbidden()
            ->assertJsonPath('code', 'TENANCY_FORBIDDEN');
    }

    public function test_logout_revokes_the_current_pos_token(): void
    {
        [$cashier, $tenant] = $this->tenantUser(MembershipType::Member, 'cashier@example.com');
        $outlet = $this->outlet($tenant, 'MAIN');
        $this->device($tenant, $outlet, '01k123456789abcdefghjkmnpq', $cashier);
        $this->role($cashier, PredefinedRole::Cashier);
        $this->assignOutlet($tenant, $outlet, $cashier);

        $token = $this->loginToken($outlet);

        $this->withToken($token)
            ->postJson(route('api.v1.pos.auth.logout'))
            ->assertOk()
            ->assertJsonPath('data.revoked', true);

        self::assertSame(0, PersonalAccessToken::query()->count());
    }

    public function test_existing_token_is_rejected_when_user_is_disabled(): void
    {
        [$cashier, $tenant] = $this->tenantUser(MembershipType::Member, 'cashier@example.com');
        $outlet = $this->outlet($tenant, 'MAIN');
        $this->device($tenant, $outlet, '01k123456789abcdefghjkmnpq', $cashier);
        $this->role($cashier, PredefinedRole::Cashier);
        $this->assignOutlet($tenant, $outlet, $cashier);

        $token = $this->loginToken($outlet);
        $cashier->forceFill(['status' => UserStatus::Disabled])->save();

        $this->withToken($token)
            ->getJson(route('api.v1.pos.outlets.context', ['outlet' => $outlet->id]))
            ->assertUnauthorized()
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_validation_errors_use_problem_details_with_request_id(): void
    {
        $response = $this->withHeader('X-Request-ID', 'test-request-1')
            ->postJson(route('api.v1.pos.auth.login'), []);

        $response
            ->assertUnprocessable()
            ->assertHeader('X-Request-ID', 'test-request-1')
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('code', 'VALIDATION_FAILED')
            ->assertJsonPath('trace_id', 'test-request-1')
            ->assertJsonStructure(['errors' => [['field', 'code', 'message']]]);
    }

    private function loginToken(Outlet $outlet): string
    {
        $response = $this->postJson(route('api.v1.pos.auth.login'), [
            'email' => 'cashier@example.com',
            'password' => 'password',
            'installation_id' => '01k123456789abcdefghjkmnpq',
            'outlet_id' => $outlet->id,
        ]);

        $response->assertOk();

        return (string) $response->json('data.access_token');
    }

    /**
     * @return array{User, Tenant}
     */
    private function tenantUser(
        MembershipType $membershipType,
        string $email = 'owner@example.com',
        ?Tenant $tenant = null,
    ): array {
        $user = User::factory()->create(['email' => $email]);
        $tenant ??= Tenant::query()->create([
            'name' => 'Tenant '.substr($user->id, -6),
            'code' => 'tenant-'.substr($user->id, -8),
            'currency' => 'IDR',
            'timezone' => 'Asia/Jakarta',
            'status' => TenantStatus::Active,
        ]);
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'membership_type' => $membershipType,
        ]);

        return [$user, $tenant];
    }

    private function role(User $user, PredefinedRole $role): void
    {
        UserRoleAssignment::query()->create([
            'user_id' => $user->id,
            'role' => $role,
        ]);
    }

    private function outlet(Tenant $tenant, string $code): Outlet
    {
        return Outlet::query()->create([
            'tenant_id' => $tenant->id,
            'name' => "{$code} Outlet",
            'code' => $code,
            'status' => OutletStatus::Active,
        ]);
    }

    private function assignOutlet(Tenant $tenant, Outlet $outlet, User $user): void
    {
        OutletUserAssignment::query()->create([
            'tenant_id' => $tenant->id,
            'outlet_id' => $outlet->id,
            'user_id' => $user->id,
        ]);
    }

    private function device(Tenant $tenant, Outlet $outlet, string $installationId, User $registeredBy): PosDevice
    {
        return PosDevice::query()->create([
            'installation_id' => $installationId,
            'tenant_id' => $tenant->id,
            'outlet_id' => $outlet->id,
            'name' => 'Front Counter',
            'client_type' => 'pos_terminal',
            'platform' => 'android',
            'status' => PosDeviceStatus::Active,
            'registered_by' => $registeredBy->id,
        ]);
    }
}
