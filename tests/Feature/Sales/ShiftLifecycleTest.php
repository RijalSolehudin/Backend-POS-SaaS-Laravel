<?php

declare(strict_types=1);

namespace Tests\Feature\Sales;

use App\Modules\Identity\Domain\Enums\PredefinedRole;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Identity\Domain\Models\UserRoleAssignment;
use App\Modules\Sales\Domain\Enums\ShiftStatus;
use App\Modules\Sales\Domain\Models\Shift;
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
use Tests\TestCase;

final class ShiftLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_opens_reads_and_closes_shift_for_bound_outlet(): void
    {
        [$cashier, $tenant, $outlet] = $this->cashierWithDevice();
        $token = $this->posToken($outlet);

        $open = $this->withToken($token)->postJson(route('api.v1.pos.outlets.shifts.open', [
            'outlet' => $outlet->id,
        ]), [
            'opening_cash_minor' => 50000,
        ]);

        $open
            ->assertCreated()
            ->assertJsonPath('data.tenant_id', $tenant->id)
            ->assertJsonPath('data.outlet_id', $outlet->id)
            ->assertJsonPath('data.user_id', $cashier->id)
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.opening_cash_minor', 50000)
            ->assertJsonPath('data.expected_cash_minor', 50000)
            ->assertJsonPath('data.currency', 'IDR');

        $shiftId = (string) $open->json('data.id');

        $this->withToken($token)->getJson(route('api.v1.pos.outlets.shifts.current', [
            'outlet' => $outlet->id,
        ]))
            ->assertOk()
            ->assertJsonPath('data.id', $shiftId)
            ->assertJsonPath('data.status', 'open');

        $this->withToken($token)->postJson(route('api.v1.pos.outlets.shifts.close', [
            'outlet' => $outlet->id,
            'shift' => $shiftId,
        ]), [
            'closing_cash_minor' => 50000,
        ])
            ->assertOk()
            ->assertJsonPath('data.id', $shiftId)
            ->assertJsonPath('data.status', 'closed')
            ->assertJsonPath('data.closing_cash_minor', 50000);

        $this->assertDatabaseHas('sales_shifts', [
            'id' => $shiftId,
            'status' => ShiftStatus::Closed->value,
            'open_shift_key' => null,
        ]);

        $this->withToken($token)->getJson(route('api.v1.pos.outlets.shifts.current', [
            'outlet' => $outlet->id,
        ]))
            ->assertOk()
            ->assertJsonPath('data', null);
    }

    public function test_duplicate_open_shift_for_same_cashier_outlet_is_rejected(): void
    {
        [, , $outlet] = $this->cashierWithDevice();
        $token = $this->posToken($outlet);

        $this->withToken($token)->postJson(route('api.v1.pos.outlets.shifts.open', [
            'outlet' => $outlet->id,
        ]), [
            'opening_cash_minor' => 10000,
        ])->assertCreated();

        $this->withToken($token)->postJson(route('api.v1.pos.outlets.shifts.open', [
            'outlet' => $outlet->id,
        ]), [
            'opening_cash_minor' => 10000,
        ])
            ->assertConflict()
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('code', 'SHIFT_ALREADY_OPEN');

        self::assertSame(1, Shift::query()->count());
    }

    public function test_closed_shift_cannot_be_closed_again(): void
    {
        [, , $outlet] = $this->cashierWithDevice();
        $token = $this->posToken($outlet);
        $shiftId = (string) $this->withToken($token)->postJson(route('api.v1.pos.outlets.shifts.open', [
            'outlet' => $outlet->id,
        ]), [
            'opening_cash_minor' => 10000,
        ])->json('data.id');

        $this->withToken($token)->postJson(route('api.v1.pos.outlets.shifts.close', [
            'outlet' => $outlet->id,
            'shift' => $shiftId,
        ]), [
            'closing_cash_minor' => 10000,
        ])->assertOk();

        $this->withToken($token)->postJson(route('api.v1.pos.outlets.shifts.close', [
            'outlet' => $outlet->id,
            'shift' => $shiftId,
        ]), [
            'closing_cash_minor' => 10000,
        ])
            ->assertConflict()
            ->assertJsonPath('code', 'SHIFT_NOT_OPEN');
    }

    public function test_shift_routes_reject_outlet_that_does_not_match_device_binding(): void
    {
        [$cashier, $tenant, $outlet] = $this->cashierWithDevice();
        $otherOutlet = $this->outlet($tenant, 'OTHER');
        $this->assignOutlet($tenant, $otherOutlet, $cashier);
        $token = $this->posToken($outlet);

        $this->withToken($token)->postJson(route('api.v1.pos.outlets.shifts.open', [
            'outlet' => $otherOutlet->id,
        ]), [
            'opening_cash_minor' => 10000,
        ])
            ->assertNotFound()
            ->assertJsonPath('code', 'OUTLET_NOT_FOUND');
    }

    /**
     * @return array{User, Tenant, Outlet}
     */
    private function cashierWithDevice(): array
    {
        $tenant = $this->tenant();
        $owner = $this->tenantUser($tenant, 'owner@example.com', MembershipType::Owner, PredefinedRole::TenantOwner);
        $cashier = $this->tenantUser($tenant, 'cashier@example.com', MembershipType::Member, PredefinedRole::Cashier);
        $outlet = $this->outlet($tenant, 'MAIN');
        $this->assignOutlet($tenant, $outlet, $cashier);
        $this->device($tenant, $outlet, '01k123456789abcdefghjkmnpq', $owner);

        return [$cashier, $tenant, $outlet];
    }

    private function tenant(): Tenant
    {
        return Tenant::query()->create([
            'name' => 'Tenant One',
            'code' => 'tenant-one',
            'currency' => 'IDR',
            'timezone' => 'Asia/Jakarta',
            'status' => TenantStatus::Active,
        ]);
    }

    private function tenantUser(
        Tenant $tenant,
        string $email,
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

    private function posToken(Outlet $outlet): string
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
}
