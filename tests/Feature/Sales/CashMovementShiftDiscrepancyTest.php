<?php

declare(strict_types=1);

namespace Tests\Feature\Sales;

use App\Modules\Identity\Domain\Enums\PredefinedRole;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Identity\Domain\Models\UserRoleAssignment;
use App\Modules\Sales\Application\Actions\ApproveSensitiveActionApproval;
use App\Modules\Sales\Application\Actions\RecordCashMovement;
use App\Modules\Sales\Application\Actions\RequestSensitiveActionApproval;
use App\Modules\Sales\Domain\Enums\CashMovementType;
use App\Modules\Sales\Domain\Models\CashMovement;
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

final class CashMovementShiftDiscrepancyTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_records_cash_in_and_cash_out_with_supervisor_approval_idempotently(): void
    {
        [$owner, $cashier, , $outlet] = $this->readyOutlet();
        $token = $this->posToken($outlet);
        $shiftId = $this->openShift($token, $outlet, 50000);

        $cashInFirst = $this->withHeader('Idempotency-Key', 'cash-in-1')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.shifts.cash-movements.store', [
                'outlet' => $outlet->id,
                'shift' => $shiftId,
            ]), [
                'type' => 'cash_in',
                'amount_minor' => 10000,
                'currency' => 'IDR',
                'reason' => 'Add drawer float',
            ]);
        $cashInReplay = $this->withHeader('Idempotency-Key', 'cash-in-1')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.shifts.cash-movements.store', [
                'outlet' => $outlet->id,
                'shift' => $shiftId,
            ]), [
                'type' => 'cash_in',
                'amount_minor' => 10000,
                'currency' => 'IDR',
                'reason' => 'Add drawer float',
            ]);

        $cashInFirst
            ->assertCreated()
            ->assertJsonPath('data.type', 'cash_in')
            ->assertJsonPath('data.amount_minor', 10000);
        $cashInReplay->assertCreated()->assertJsonPath('data.id', $cashInFirst->json('data.id'));

        $this->withHeader('Idempotency-Key', 'cash-out-missing-approval')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.shifts.cash-movements.store', [
                'outlet' => $outlet->id,
                'shift' => $shiftId,
            ]), [
                'type' => 'cash_out',
                'amount_minor' => 5000,
                'currency' => 'IDR',
                'reason' => 'Petty cash purchase',
            ])
            ->assertConflict()
            ->assertJsonPath('code', 'APPROVAL_REQUIRED');

        $approval = app(RequestSensitiveActionApproval::class)->handle(
            tenantId: $outlet->tenant_id,
            outletId: $outlet->id,
            performerUserId: $cashier->id,
            action: 'cash_movements.cash_out',
            targetType: 'sales_shift',
            targetId: $shiftId,
            requestFingerprint: RecordCashMovement::approvalFingerprint($shiftId, CashMovementType::CashOut, 5000, 'IDR', 'Petty cash purchase'),
            reason: 'Petty cash purchase',
            idempotencyKey: 'cash-out-approval-1',
        );
        app(ApproveSensitiveActionApproval::class)->approve($outlet->tenant_id, $approval->id, $owner->id, 'Approved cash out');

        $this->withHeader('Idempotency-Key', 'cash-out-1')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.shifts.cash-movements.store', [
                'outlet' => $outlet->id,
                'shift' => $shiftId,
            ]), [
                'type' => 'cash_out',
                'amount_minor' => 5000,
                'currency' => 'IDR',
                'reason' => 'Petty cash purchase',
                'approval_id' => $approval->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.type', 'cash_out')
            ->assertJsonPath('data.approval_id', $approval->id);

        self::assertSame(2, CashMovement::query()->count());
        $this->assertDatabaseHas('sales_sensitive_action_approvals', [
            'id' => $approval->id,
            'status' => 'consumed',
        ]);
        $this->assertDatabaseHas('sales_audit_events', [
            'event_type' => 'cash_movement.recorded',
            'target_id' => $cashInFirst->json('data.id'),
        ]);

        $this->withToken($token)->getJson(route('api.v1.pos.outlets.shifts.summary', [
            'outlet' => $outlet->id,
            'shift' => $shiftId,
        ]))
            ->assertOk()
            ->assertJsonPath('data.cash_in_minor', 10000)
            ->assertJsonPath('data.cash_out_minor', 5000)
            ->assertJsonPath('data.expected_cash_minor', 55000);
    }

    public function test_cash_movement_rejects_closed_shift_and_idempotency_conflict_and_close_records_discrepancy(): void
    {
        [$owner, $cashier, , $outlet] = $this->readyOutlet();
        $token = $this->posToken($outlet);
        $shiftId = $this->openShift($token, $outlet, 50000);

        $this->withHeader('Idempotency-Key', 'cash-in-conflict')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.shifts.cash-movements.store', [
                'outlet' => $outlet->id,
                'shift' => $shiftId,
            ]), [
                'type' => 'cash_in',
                'amount_minor' => 10000,
                'currency' => 'IDR',
                'reason' => 'Add drawer float',
            ])
            ->assertCreated();
        $this->withHeader('Idempotency-Key', 'cash-in-conflict')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.shifts.cash-movements.store', [
                'outlet' => $outlet->id,
                'shift' => $shiftId,
            ]), [
                'type' => 'cash_in',
                'amount_minor' => 20000,
                'currency' => 'IDR',
                'reason' => 'Different amount',
            ])
            ->assertConflict()
            ->assertJsonPath('code', 'IDEMPOTENCY_CONFLICT');

        $approval = app(RequestSensitiveActionApproval::class)->handle(
            tenantId: $outlet->tenant_id,
            outletId: $outlet->id,
            performerUserId: $cashier->id,
            action: 'cash_movements.cash_out',
            targetType: 'sales_shift',
            targetId: $shiftId,
            requestFingerprint: RecordCashMovement::approvalFingerprint($shiftId, CashMovementType::CashOut, 3000, 'IDR', 'Bank deposit'),
            reason: 'Bank deposit',
            idempotencyKey: 'cash-out-approval-2',
        );
        app(ApproveSensitiveActionApproval::class)->approve($outlet->tenant_id, $approval->id, $owner->id, 'Approved cash out');

        $this->withHeader('Idempotency-Key', 'cash-out-before-close')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.shifts.cash-movements.store', [
                'outlet' => $outlet->id,
                'shift' => $shiftId,
            ]), [
                'type' => 'cash_out',
                'amount_minor' => 3000,
                'currency' => 'IDR',
                'reason' => 'Bank deposit',
                'approval_id' => $approval->id,
            ])
            ->assertCreated();

        $this->withToken($token)->postJson(route('api.v1.pos.outlets.shifts.close', [
            'outlet' => $outlet->id,
            'shift' => $shiftId,
        ]), [
            'closing_cash_minor' => 56000,
        ])->assertOk();

        $this->assertDatabaseHas('sales_shifts', [
            'id' => $shiftId,
            'expected_cash_minor' => 57000,
            'closing_cash_minor' => 56000,
        ]);
        $this->assertDatabaseHas('sales_audit_events', [
            'event_type' => 'shift.discrepancy.recorded',
            'target_id' => $shiftId,
        ]);

        $this->withHeader('Idempotency-Key', 'cash-in-closed')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.shifts.cash-movements.store', [
                'outlet' => $outlet->id,
                'shift' => $shiftId,
            ]), [
                'type' => 'cash_in',
                'amount_minor' => 1000,
                'currency' => 'IDR',
                'reason' => 'Late drawer adjustment',
            ])
            ->assertConflict()
            ->assertJsonPath('code', 'CASH_MOVEMENT_SHIFT_NOT_OPEN');
    }

    /**
     * @return array{User, User, Tenant, Outlet}
     */
    private function readyOutlet(): array
    {
        $tenant = Tenant::query()->create([
            'name' => 'Tenant One',
            'code' => 'tenant-one',
            'currency' => 'IDR',
            'timezone' => 'Asia/Jakarta',
            'status' => TenantStatus::Active,
        ]);
        $owner = $this->tenantUser($tenant, 'owner@example.com', MembershipType::Owner, PredefinedRole::TenantOwner);
        $cashier = $this->tenantUser($tenant, 'cashier@example.com', MembershipType::Member, PredefinedRole::Cashier);
        $outlet = Outlet::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Main Outlet',
            'code' => 'MAIN',
            'status' => OutletStatus::Active,
        ]);
        OutletUserAssignment::query()->create([
            'tenant_id' => $tenant->id,
            'outlet_id' => $outlet->id,
            'user_id' => $cashier->id,
        ]);
        PosDevice::query()->create([
            'installation_id' => '01k123456789abcdefghjkmnpq',
            'tenant_id' => $tenant->id,
            'outlet_id' => $outlet->id,
            'name' => 'Front Counter',
            'client_type' => 'pos_terminal',
            'platform' => 'android',
            'status' => PosDeviceStatus::Active,
            'registered_by' => $owner->id,
        ]);

        return [$owner, $cashier, $tenant, $outlet];
    }

    private function tenantUser(Tenant $tenant, string $email, MembershipType $membershipType, PredefinedRole $role): User
    {
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

    private function openShift(string $token, Outlet $outlet, int $openingCashMinor): string
    {
        return (string) $this->withToken($token)->postJson(route('api.v1.pos.outlets.shifts.open', [
            'outlet' => $outlet->id,
        ]), [
            'opening_cash_minor' => $openingCashMinor,
        ])
            ->assertCreated()
            ->json('data.id');
    }
}
