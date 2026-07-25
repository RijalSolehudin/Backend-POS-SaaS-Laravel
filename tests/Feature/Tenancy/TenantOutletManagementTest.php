<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Modules\Identity\Domain\Models\User;
use App\Modules\Tenancy\Domain\Enums\MembershipType;
use App\Modules\Tenancy\Domain\Enums\OutletStatus;
use App\Modules\Tenancy\Domain\Enums\TenantStatus;
use App\Modules\Tenancy\Domain\Models\Outlet;
use App\Modules\Tenancy\Domain\Models\OutletUserAssignment;
use App\Modules\Tenancy\Domain\Models\Tenant;
use App\Modules\Tenancy\Domain\Models\TenantMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TenantOutletManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_update_and_disable_an_outlet(): void
    {
        [$owner, $tenant] = $this->tenantUser(MembershipType::Owner);
        $this->login($owner);

        $this->post(route('tenant.outlets.store', ['tenant' => $tenant->id]), [
            'name' => 'North Branch',
            'code' => 'north',
        ])->assertRedirect();

        $outlet = Outlet::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $this->assertSame('NORTH', $outlet->code);

        $this->put(route('tenant.outlets.update', ['tenant' => $tenant->id, 'outlet' => $outlet->id]), [
            'name' => 'North Flagship',
            'code' => 'north-1',
        ])->assertRedirect();
        $this->assertDatabaseHas('outlets', [
            'id' => $outlet->id,
            'tenant_id' => $tenant->id,
            'name' => 'North Flagship',
            'code' => 'NORTH-1',
        ]);

        $this->post(route('tenant.outlets.disable', ['tenant' => $tenant->id, 'outlet' => $outlet->id]), [
            'reason' => 'Branch operations have ended.',
        ])->assertRedirect();
        $this->assertSame(OutletStatus::Disabled, $outlet->refresh()->status);
        $this->assertDatabaseHas('tenancy_audit_events', [
            'event_type' => 'outlet.disabled',
            'target_tenant_id' => $tenant->id,
        ]);
    }

    public function test_non_owner_cannot_open_outlet_administration(): void
    {
        [$member, $tenant] = $this->tenantUser(MembershipType::Member);
        $this->login($member);

        $this->get(route('tenant.outlets.index', ['tenant' => $tenant->id]))
            ->assertForbidden();
    }

    public function test_outlet_from_another_tenant_cannot_be_read_or_mutated(): void
    {
        [$owner, $tenant] = $this->tenantUser(MembershipType::Owner);
        [, $otherTenant] = $this->tenantUser(MembershipType::Owner, 'other-owner@example.com');
        $otherOutlet = $this->outlet($otherTenant, 'OTHER');
        $this->login($owner);

        $this->get(route('tenant.outlets.edit', [
            'tenant' => $tenant->id,
            'outlet' => $otherOutlet->id,
        ]))->assertNotFound();

        $this->put(route('tenant.outlets.update', [
            'tenant' => $tenant->id,
            'outlet' => $otherOutlet->id,
        ]), [
            'name' => 'Compromised name',
            'code' => 'HACKED',
        ])->assertNotFound();

        $this->assertSame('Other Outlet', $otherOutlet->refresh()->name);
    }

    public function test_owner_can_assign_one_tenant_user_to_multiple_owning_tenant_outlets_only(): void
    {
        [$owner, $tenant] = $this->tenantUser(MembershipType::Owner);
        [$member] = $this->tenantUser(MembershipType::Member, 'member@example.com', $tenant);
        [$outsider] = $this->tenantUser(MembershipType::Member, 'outsider@example.com');
        $first = $this->outlet($tenant, 'FIRST');
        $second = $this->outlet($tenant, 'SECOND');
        $this->login($owner);

        foreach ([$first, $second] as $outlet) {
            $this->post(route('tenant.outlets.users.assign', [
                'tenant' => $tenant->id,
                'outlet' => $outlet->id,
            ]), ['user_id' => $member->id])->assertRedirect();
        }

        $this->assertSame(2, OutletUserAssignment::query()->where('user_id', $member->id)->count());

        $this->post(route('tenant.outlets.users.assign', [
            'tenant' => $tenant->id,
            'outlet' => $first->id,
        ]), ['user_id' => $outsider->id])->assertNotFound();
        $this->assertDatabaseMissing('outlet_user_assignments', [
            'outlet_id' => $first->id,
            'user_id' => $outsider->id,
        ]);

        $this->delete(route('tenant.outlets.users.remove', [
            'tenant' => $tenant->id,
            'outlet' => $first->id,
            'user' => $member->id,
        ]))->assertRedirect();
        $this->assertDatabaseMissing('outlet_user_assignments', [
            'outlet_id' => $first->id,
            'user_id' => $member->id,
        ]);
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

    private function outlet(Tenant $tenant, string $code): Outlet
    {
        return Outlet::query()->create([
            'tenant_id' => $tenant->id,
            'name' => $code === 'OTHER' ? 'Other Outlet' : "{$code} Outlet",
            'code' => $code,
            'status' => OutletStatus::Active,
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
