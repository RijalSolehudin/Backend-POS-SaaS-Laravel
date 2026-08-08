<?php

declare(strict_types=1);

namespace Tests\Feature\Dining;

use App\Modules\Dining\Application\Actions\CreateDiningTable;
use App\Modules\Dining\Application\Data\DiningTableInput;
use App\Modules\Dining\Domain\Enums\TableStatus;
use App\Modules\Dining\Domain\Models\DiningFloor;
use App\Modules\Dining\Domain\Models\DiningTable;
use App\Modules\Identity\Domain\Enums\PredefinedRole;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Identity\Domain\Models\UserRoleAssignment;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Domain\Enums\MembershipType;
use App\Modules\Tenancy\Domain\Enums\OutletStatus;
use App\Modules\Tenancy\Domain\Enums\TenantStatus;
use App\Modules\Tenancy\Domain\Models\Outlet;
use App\Modules\Tenancy\Domain\Models\Tenant;
use App\Modules\Tenancy\Domain\Models\TenantMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DiningFloorTableFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_manages_dining_floors_and_tables(): void
    {
        $tenant = $this->tenant();
        $owner = $this->user('owner@example.com', $tenant, MembershipType::Owner, PredefinedRole::TenantOwner);
        $outlet = $this->outlet($tenant, 'MAIN');
        $this->login($owner);

        $this->get(route('tenant.dining.index', ['tenant' => $tenant->id]))
            ->assertOk()
            ->assertSee('Dining')
            ->assertSee('Floors')
            ->assertSee('Tables');

        $this->post(route('tenant.dining.floors.store', ['tenant' => $tenant->id]), [
            'outlet_id' => $outlet->id,
            'name' => 'Main Floor',
            'code' => ' main ',
            'display_order' => 10,
        ])->assertRedirect();

        $floor = DiningFloor::query()->where('tenant_id', $tenant->id)->firstOrFail();

        self::assertSame($outlet->id, $floor->outlet_id);
        self::assertSame('MAIN', $floor->code);
        self::assertSame(TableStatus::Active, $floor->status);

        $this->post(route('tenant.dining.tables.store', ['tenant' => $tenant->id]), [
            'outlet_id' => $outlet->id,
            'floor_id' => $floor->id,
            'name' => 'Table 1',
            'code' => ' t01 ',
            'capacity' => 4,
            'display_order' => 1,
        ])->assertRedirect();

        $table = DiningTable::query()->where('tenant_id', $tenant->id)->firstOrFail();

        self::assertSame($floor->id, $table->floor_id);
        self::assertSame('T01', $table->code);
        self::assertSame(4, $table->capacity);

        $this->put(route('tenant.dining.tables.update', [
            'tenant' => $tenant->id,
            'table' => $table->id,
        ]), [
            'outlet_id' => $outlet->id,
            'floor_id' => $floor->id,
            'name' => 'Window Table',
            'code' => 'W01',
            'capacity' => 2,
            'display_order' => 2,
        ])->assertRedirect();

        self::assertSame('Window Table', $table->refresh()->name);
        self::assertSame(2, $table->capacity);

        $this->post(route('tenant.dining.tables.status', [
            'tenant' => $tenant->id,
            'table' => $table->id,
        ]), ['status' => 'inactive'])->assertRedirect();

        self::assertSame(TableStatus::Inactive, $table->refresh()->status);
    }

    public function test_duplicate_codes_are_rejected_within_same_outlet(): void
    {
        $tenant = $this->tenant();
        $owner = $this->user('owner@example.com', $tenant, MembershipType::Owner, PredefinedRole::TenantOwner);
        $outlet = $this->outlet($tenant, 'MAIN');
        $floor = $this->floor($tenant, $outlet, 'MAIN');
        $this->table($tenant, $outlet, $floor, 'T01');
        $this->login($owner);

        $this->from(route('tenant.dining.index', ['tenant' => $tenant->id]))
            ->post(route('tenant.dining.floors.store', ['tenant' => $tenant->id]), [
                'outlet_id' => $outlet->id,
                'name' => 'Duplicate Main',
                'code' => ' main ',
                'display_order' => 20,
            ])
            ->assertRedirect(route('tenant.dining.index', ['tenant' => $tenant->id]))
            ->assertSessionHasErrors('code');

        $this->from(route('tenant.dining.index', ['tenant' => $tenant->id]))
            ->post(route('tenant.dining.tables.store', ['tenant' => $tenant->id]), [
                'outlet_id' => $outlet->id,
                'floor_id' => $floor->id,
                'name' => 'Duplicate Table',
                'code' => ' t01 ',
                'capacity' => 2,
                'display_order' => 3,
            ])
            ->assertRedirect(route('tenant.dining.index', ['tenant' => $tenant->id]))
            ->assertSessionHasErrors('code');
    }

    public function test_cross_tenant_and_cross_outlet_references_are_rejected(): void
    {
        $tenant = $this->tenant();
        $otherTenant = $this->tenant('Other Tenant', 'other');
        $owner = $this->user('owner@example.com', $tenant, MembershipType::Owner, PredefinedRole::TenantOwner);
        $outlet = $this->outlet($tenant, 'MAIN');
        $otherOutlet = $this->outlet($otherTenant, 'OTHER');
        $floor = $this->floor($tenant, $outlet, 'MAIN');
        $otherFloor = $this->floor($otherTenant, $otherOutlet, 'OTHER');
        $secondOutlet = $this->outlet($tenant, 'SIDE');
        $secondFloor = $this->floor($tenant, $secondOutlet, 'SIDE');
        $this->login($owner);

        $this->from(route('tenant.dining.index', ['tenant' => $tenant->id]))
            ->post(route('tenant.dining.floors.store', ['tenant' => $tenant->id]), [
                'outlet_id' => $otherOutlet->id,
                'name' => 'Other Floor',
                'code' => 'OTHER',
                'display_order' => 1,
            ])
            ->assertRedirect(route('tenant.dining.index', ['tenant' => $tenant->id]))
            ->assertSessionHasErrors('outlet_id');

        $this->from(route('tenant.dining.index', ['tenant' => $tenant->id]))
            ->post(route('tenant.dining.tables.store', ['tenant' => $tenant->id]), [
                'outlet_id' => $outlet->id,
                'floor_id' => $otherFloor->id,
                'name' => 'Other Floor Table',
                'code' => 'X01',
                'capacity' => 2,
                'display_order' => 1,
            ])
            ->assertRedirect(route('tenant.dining.index', ['tenant' => $tenant->id]))
            ->assertSessionHasErrors('floor_id');

        $this->from(route('tenant.dining.index', ['tenant' => $tenant->id]))
            ->post(route('tenant.dining.tables.store', ['tenant' => $tenant->id]), [
                'outlet_id' => $outlet->id,
                'floor_id' => $secondFloor->id,
                'name' => 'Wrong Outlet Floor',
                'code' => 'X02',
                'capacity' => 2,
                'display_order' => 1,
            ])
            ->assertRedirect(route('tenant.dining.index', ['tenant' => $tenant->id]))
            ->assertSessionHasErrors('floor_id');

        $this->from(route('tenant.dining.index', ['tenant' => $tenant->id]))
            ->put(route('tenant.dining.floors.update', [
                'tenant' => $tenant->id,
                'floor' => $floor->id,
            ]), [
                'outlet_id' => $secondOutlet->id,
                'name' => 'Moved Floor',
                'code' => 'MAIN',
                'display_order' => 1,
            ])
            ->assertRedirect(route('tenant.dining.index', ['tenant' => $tenant->id]))
            ->assertSessionHasErrors('outlet_id');
    }

    public function test_non_owner_cannot_access_dining_admin(): void
    {
        $tenant = $this->tenant();
        $manager = $this->user('manager@example.com', $tenant, MembershipType::Member, PredefinedRole::OutletManager);
        $this->login($manager);

        $this->get(route('tenant.dining.index', ['tenant' => $tenant->id]))
            ->assertForbidden();
    }

    public function test_application_action_rejects_cross_outlet_floor(): void
    {
        $tenant = $this->tenant();
        $owner = $this->user('owner@example.com', $tenant, MembershipType::Owner, PredefinedRole::TenantOwner);
        $outlet = $this->outlet($tenant, 'MAIN');
        $otherOutlet = $this->outlet($tenant, 'SIDE');
        $otherFloor = $this->floor($tenant, $otherOutlet, 'SIDE');
        $context = new TenantRequestContext($tenant->id, $owner->id, MembershipType::Owner);

        $this->expectExceptionMessage('The selected dining floor belongs to another outlet.');

        $this->app->make(CreateDiningTable::class)->handle(
            $context,
            new DiningTableInput(
                outletId: $outlet->id,
                floorId: $otherFloor->id,
                name: 'Invalid Table',
                code: 'X01',
                capacity: 2,
                displayOrder: 1,
            ),
        );
    }

    private function tenant(string $name = 'Acme POS', string $code = 'acme-pos'): Tenant
    {
        return Tenant::query()->create([
            'name' => $name,
            'code' => $code,
            'status' => TenantStatus::Active,
            'currency' => 'IDR',
            'timezone' => 'Asia/Jakarta',
        ]);
    }

    private function user(
        string $email,
        Tenant $tenant,
        MembershipType $membershipType,
        PredefinedRole $role,
    ): User {
        $user = User::factory()->create(['email' => $email]);
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'membership_type' => $membershipType,
        ]);
        UserRoleAssignment::query()->create([
            'user_id' => $user->id,
            'role' => $role,
        ]);

        return $user;
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

    private function floor(Tenant $tenant, Outlet $outlet, string $code): DiningFloor
    {
        return DiningFloor::query()->create([
            'tenant_id' => $tenant->id,
            'outlet_id' => $outlet->id,
            'name' => "{$code} Floor",
            'code' => $code,
            'display_order' => 1,
            'status' => TableStatus::Active,
        ]);
    }

    private function table(Tenant $tenant, Outlet $outlet, DiningFloor $floor, string $code): DiningTable
    {
        return DiningTable::query()->create([
            'tenant_id' => $tenant->id,
            'outlet_id' => $outlet->id,
            'floor_id' => $floor->id,
            'name' => "{$code} Table",
            'code' => $code,
            'capacity' => 2,
            'display_order' => 1,
            'status' => TableStatus::Active,
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
