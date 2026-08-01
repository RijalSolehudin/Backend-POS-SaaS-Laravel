<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Modules\Identity\Domain\Enums\PredefinedRole;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Identity\Domain\Models\UserRoleAssignment;
use App\Modules\Tenancy\Application\Actions\AssignPredefinedRole;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Exceptions\TenancyException;
use App\Modules\Tenancy\Domain\Enums\MembershipType;
use App\Modules\Tenancy\Domain\Enums\TenantStatus;
use App\Modules\Tenancy\Domain\Models\Tenant;
use App\Modules\Tenancy\Domain\Models\TenantMembership;
use App\Shared\Application\Context\ActorContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TenantPredefinedRbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_assign_and_remove_predefined_roles_from_tenant_users(): void
    {
        [$owner, $tenant] = $this->tenantUser(MembershipType::Owner);
        [$member] = $this->tenantUser(MembershipType::Member, 'member@example.com', $tenant);
        $this->role($owner, PredefinedRole::TenantOwner);
        $this->login($owner);

        $this->get(route('tenant.users.index', ['tenant' => $tenant->id]))
            ->assertOk()
            ->assertSee('Tenant Owner')
            ->assertSee('Outlet Manager')
            ->assertSee('Cashier');

        $this->post(route('tenant.users.roles.store', ['tenant' => $tenant->id, 'user' => $member->id]), [
            'role' => PredefinedRole::Cashier->value,
        ])->assertRedirect();

        $this->assertDatabaseHas('user_role_assignments', [
            'user_id' => $member->id,
            'role' => PredefinedRole::Cashier->value,
        ]);
        $this->assertDatabaseHas('tenancy_audit_events', [
            'event_type' => 'tenant_role.assigned',
            'target_tenant_id' => $tenant->id,
        ]);

        $this->delete(route('tenant.users.roles.destroy', [
            'tenant' => $tenant->id,
            'user' => $member->id,
            'role' => PredefinedRole::Cashier->value,
        ]))->assertRedirect();

        $this->assertDatabaseMissing('user_role_assignments', [
            'user_id' => $member->id,
            'role' => PredefinedRole::Cashier->value,
        ]);
        $this->assertDatabaseHas('tenancy_audit_events', [
            'event_type' => 'tenant_role.removed',
            'target_tenant_id' => $tenant->id,
        ]);
    }

    public function test_cross_tenant_role_assignment_is_rejected_without_mutation(): void
    {
        [$owner, $tenant] = $this->tenantUser(MembershipType::Owner);
        [$outsider] = $this->tenantUser(MembershipType::Member, 'outsider@example.com');
        $this->role($owner, PredefinedRole::TenantOwner);
        $this->login($owner);

        $this->from(route('tenant.users.index', ['tenant' => $tenant->id]))
            ->post(route('tenant.users.roles.store', ['tenant' => $tenant->id, 'user' => $outsider->id]), [
                'role' => PredefinedRole::OutletManager->value,
            ])
            ->assertRedirect(route('tenant.users.index', ['tenant' => $tenant->id]));

        $this->assertDatabaseMissing('user_role_assignments', [
            'user_id' => $outsider->id,
            'role' => PredefinedRole::OutletManager->value,
        ]);
    }

    public function test_cashier_has_no_administrative_capability_even_at_the_use_case_boundary(): void
    {
        [$cashier, $tenant] = $this->tenantUser(MembershipType::Member);
        [$member] = $this->tenantUser(MembershipType::Member, 'member@example.com', $tenant);
        $this->role($cashier, PredefinedRole::Cashier);

        try {
            app(AssignPredefinedRole::class)->handle(
                new TenantRequestContext($tenant->id, $cashier->id, MembershipType::Member),
                $member->id,
                PredefinedRole::OutletManager->value,
                new ActorContext('tenant_user', $cashier->id, 'rbac-test'),
            );
            self::fail('Expected cashier role assignment to be rejected.');
        } catch (TenancyException $exception) {
            self::assertSame('TENANCY_FORBIDDEN', $exception->errorCode());
        }

        $this->assertDatabaseMissing('user_role_assignments', [
            'user_id' => $member->id,
            'role' => PredefinedRole::OutletManager->value,
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

    private function role(User $user, PredefinedRole $role): void
    {
        UserRoleAssignment::query()->create([
            'user_id' => $user->id,
            'role' => $role,
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
