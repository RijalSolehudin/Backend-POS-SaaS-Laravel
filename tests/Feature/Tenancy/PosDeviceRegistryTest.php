<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Modules\Identity\Domain\Enums\PredefinedRole;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Identity\Domain\Models\UserRoleAssignment;
use App\Modules\Tenancy\Application\Actions\ResolveRegisteredPosDevice;
use App\Modules\Tenancy\Application\Exceptions\DeviceRegistryException;
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
use Tests\TestCase;

final class PosDeviceRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_outlet_manager_can_register_a_pos_device_for_an_assigned_outlet(): void
    {
        [$manager, $tenant] = $this->tenantUser(MembershipType::Member);
        $outlet = $this->outlet($tenant, 'MAIN');
        $this->role($manager, PredefinedRole::OutletManager);
        $this->assignOutlet($tenant, $outlet, $manager);
        $this->login($manager);

        $this->get(route('tenant.devices.index', ['tenant' => $tenant->id]))
            ->assertOk()
            ->assertSee('POS devices')
            ->assertSee($outlet->name);

        $this->post(route('tenant.devices.store', ['tenant' => $tenant->id]), [
            'installation_id' => '01k123456789abcdefghjkmnpq',
            'name' => 'Front Counter A',
            'outlet_id' => $outlet->id,
            'platform' => 'Android',
            'app_version' => '1.0.0',
        ])->assertRedirect();

        $this->assertDatabaseHas('pos_devices', [
            'installation_id' => '01k123456789abcdefghjkmnpq',
            'tenant_id' => $tenant->id,
            'outlet_id' => $outlet->id,
            'status' => PosDeviceStatus::Active->value,
            'registered_by' => $manager->id,
        ]);
        $this->assertDatabaseHas('tenancy_audit_events', [
            'event_type' => 'pos_device.registered',
            'target_tenant_id' => $tenant->id,
        ]);
    }

    public function test_cashier_cannot_register_a_pos_device(): void
    {
        [$cashier, $tenant] = $this->tenantUser(MembershipType::Member);
        $outlet = $this->outlet($tenant, 'MAIN');
        $this->role($cashier, PredefinedRole::Cashier);
        $this->assignOutlet($tenant, $outlet, $cashier);
        $this->login($cashier);

        $this->get(route('tenant.devices.index', ['tenant' => $tenant->id]))
            ->assertForbidden();

        $this->post(route('tenant.devices.store', ['tenant' => $tenant->id]), [
            'installation_id' => '01k123456789abcdefghjkmnpq',
            'name' => 'Cashier Device',
            'outlet_id' => $outlet->id,
            'platform' => 'android',
        ])->assertForbidden();

        $this->assertDatabaseMissing('pos_devices', [
            'installation_id' => '01k123456789abcdefghjkmnpq',
        ]);
    }

    public function test_owner_reassigns_a_device_within_the_tenant_and_revokes_linked_tokens(): void
    {
        [$owner, $tenant] = $this->tenantUser(MembershipType::Owner);
        $first = $this->outlet($tenant, 'FIRST');
        $second = $this->outlet($tenant, 'SECOND');
        $this->role($owner, PredefinedRole::TenantOwner);
        $device = $this->device($tenant, $first, '01k123456789abcdefghjkmnpq', $owner);
        $this->linkedToken($owner, $device);
        $this->login($owner);

        $this->put(route('tenant.devices.reassign', ['tenant' => $tenant->id, 'device' => $device->id]), [
            'outlet_id' => $second->id,
            'reason' => 'Moved terminal to another counter.',
        ])->assertRedirect();

        $this->assertSame($second->id, $device->refresh()->outlet_id);
        $this->assertDatabaseMissing('personal_access_tokens', ['pos_device_id' => $device->id]);
        $this->assertDatabaseHas('tenancy_audit_events', [
            'event_type' => 'pos_device.reassigned',
            'target_tenant_id' => $tenant->id,
        ]);
    }

    public function test_reassignment_to_another_tenant_outlet_is_rejected_without_mutation(): void
    {
        [$owner, $tenant] = $this->tenantUser(MembershipType::Owner);
        [, $otherTenant] = $this->tenantUser(MembershipType::Owner, 'other-owner@example.com');
        $first = $this->outlet($tenant, 'FIRST');
        $otherOutlet = $this->outlet($otherTenant, 'OTHER');
        $this->role($owner, PredefinedRole::TenantOwner);
        $device = $this->device($tenant, $first, '01k123456789abcdefghjkmnpq', $owner);
        $this->login($owner);

        $this->from(route('tenant.devices.index', ['tenant' => $tenant->id]))
            ->put(route('tenant.devices.reassign', ['tenant' => $tenant->id, 'device' => $device->id]), [
                'outlet_id' => $otherOutlet->id,
                'reason' => 'Attempted cross tenant move.',
            ])
            ->assertRedirect(route('tenant.devices.index', ['tenant' => $tenant->id]));

        $this->assertSame($first->id, $device->refresh()->outlet_id);
    }

    public function test_revocation_keeps_device_record_and_revokes_linked_tokens(): void
    {
        [$owner, $tenant] = $this->tenantUser(MembershipType::Owner);
        $outlet = $this->outlet($tenant, 'MAIN');
        $this->role($owner, PredefinedRole::TenantOwner);
        $device = $this->device($tenant, $outlet, '01k123456789abcdefghjkmnpq', $owner);
        $this->linkedToken($owner, $device);
        $this->login($owner);

        $this->post(route('tenant.devices.revoke', ['tenant' => $tenant->id, 'device' => $device->id]), [
            'reason' => 'Terminal was retired.',
        ])->assertRedirect();

        $this->assertDatabaseHas('pos_devices', [
            'id' => $device->id,
            'status' => PosDeviceStatus::Revoked->value,
            'revoked_by' => $owner->id,
        ]);
        $this->assertDatabaseMissing('personal_access_tokens', ['pos_device_id' => $device->id]);
        $this->assertDatabaseHas('tenancy_audit_events', [
            'event_type' => 'pos_device.revoked',
            'target_tenant_id' => $tenant->id,
        ]);
    }

    public function test_device_resolution_uses_stable_error_codes_for_unknown_and_revoked_devices(): void
    {
        [$owner, $tenant] = $this->tenantUser(MembershipType::Owner);
        $outlet = $this->outlet($tenant, 'MAIN');
        $device = $this->device($tenant, $outlet, '01k123456789abcdefghjkmnpq', $owner);
        $resolver = app(ResolveRegisteredPosDevice::class);

        self::assertSame($device->id, $resolver->handle($tenant->id, '01k123456789abcdefghjkmnpq')->id);

        try {
            $resolver->handle($tenant->id, '01k123456789abcdefghjkmnpr');
            self::fail('Expected unknown device rejection.');
        } catch (DeviceRegistryException $exception) {
            self::assertSame('DEVICE_NOT_REGISTERED', $exception->errorCode());
        }

        $device->forceFill(['status' => PosDeviceStatus::Revoked])->save();

        try {
            $resolver->handle($tenant->id, '01k123456789abcdefghjkmnpq');
            self::fail('Expected revoked device rejection.');
        } catch (DeviceRegistryException $exception) {
            self::assertSame('DEVICE_REVOKED', $exception->errorCode());
        }
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

    private function linkedToken(User $user, PosDevice $device): void
    {
        DB::table('personal_access_tokens')->insert([
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
            'pos_device_id' => $device->id,
            'name' => 'POS device',
            'token' => hash('sha256', $device->id),
            'abilities' => json_encode(['*']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function login(User $user): void
    {
        $this->actingAs($user, 'web')->withSession([
            'tenant.authenticated_at' => now()->getTimestamp(),
            'tenant.last_activity_at' => now()->getTimestamp(),
        ]);
    }
}
