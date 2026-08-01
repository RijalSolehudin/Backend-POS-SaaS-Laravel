<?php

declare(strict_types=1);

namespace Tests\Feature\Sales;

use App\Modules\Catalog\Domain\Enums\CategoryStatus;
use App\Modules\Catalog\Domain\Enums\ProductStatus;
use App\Modules\Catalog\Domain\Models\Category;
use App\Modules\Catalog\Domain\Models\Product;
use App\Modules\Catalog\Domain\Models\ProductOutletAvailability;
use App\Modules\Identity\Domain\Enums\PredefinedRole;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Identity\Domain\Models\UserRoleAssignment;
use App\Modules\Sales\Application\Actions\ApproveSensitiveActionApproval;
use App\Modules\Sales\Application\Actions\RequestSensitiveActionApproval;
use App\Modules\Sales\Application\Actions\VoidCompletedOrder;
use App\Modules\Sales\Application\Exceptions\ApprovalException;
use App\Modules\Sales\Domain\Models\Order;
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

final class SensitiveActionApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_sensitive_action_approval_is_idempotent_and_consumed_by_matching_void_action(): void
    {
        [$owner, $manager, $cashier, $tenant, $outlet] = $this->readyOutlet();
        $token = $this->posToken($outlet);
        $orderId = $this->completedOrder($tenant, $outlet, $token);
        $reason = 'Wrong payment method selected';
        $fingerprint = VoidCompletedOrder::approvalFingerprint($orderId, $reason);

        $first = app(RequestSensitiveActionApproval::class)->handle(
            tenantId: $tenant->id,
            outletId: $outlet->id,
            performerUserId: $cashier->id,
            action: 'orders.void',
            targetType: 'sales_order',
            targetId: $orderId,
            requestFingerprint: $fingerprint,
            reason: $reason,
            idempotencyKey: 'request-approval-1',
        );
        $second = app(RequestSensitiveActionApproval::class)->handle(
            tenantId: $tenant->id,
            outletId: $outlet->id,
            performerUserId: $cashier->id,
            action: 'orders.void',
            targetType: 'sales_order',
            targetId: $orderId,
            requestFingerprint: $fingerprint,
            reason: $reason,
            idempotencyKey: 'request-approval-1',
        );

        self::assertSame($first->id, $second->id);
        app(ApproveSensitiveActionApproval::class)->approve($tenant->id, $first->id, $manager->id, 'Approved by outlet manager');

        $this->withHeader('Idempotency-Key', 'void-with-approval-1')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.void', [
                'outlet' => $outlet->id,
                'order' => $orderId,
            ]), [
                'reason' => $reason,
                'approval_id' => $first->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'voided');

        $this->assertDatabaseHas('sales_sensitive_action_approvals', [
            'id' => $first->id,
            'approver_user_id' => $manager->id,
            'status' => 'consumed',
        ]);
        $this->assertDatabaseHas('sales_audit_events', [
            'event_type' => 'approval.created',
            'target_id' => $first->id,
        ]);
        $this->assertDatabaseHas('sales_audit_events', [
            'event_type' => 'approval.approved',
            'target_id' => $first->id,
        ]);
        $this->assertDatabaseHas('sales_audit_events', [
            'event_type' => 'approval.consumed',
            'target_id' => $first->id,
        ]);

        $this->withHeader('Idempotency-Key', 'void-different-key-after-consumed')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.void', [
                'outlet' => $outlet->id,
                'order' => $orderId,
            ]), [
                'reason' => $reason,
                'approval_id' => $first->id,
            ])
            ->assertConflict()
            ->assertJsonPath('code', 'ORDER_NOT_COMPLETED');

        self::assertTrue($owner->isActive());
    }

    public function test_approval_rejects_same_actor_cashier_and_fingerprint_mismatch(): void
    {
        [$owner, , $cashier, $tenant, $outlet] = $this->readyOutlet();
        $token = $this->posToken($outlet);
        $orderId = $this->completedOrder($tenant, $outlet, $token);
        $reason = 'Wrong payment method selected';

        $approval = app(RequestSensitiveActionApproval::class)->handle(
            tenantId: $tenant->id,
            outletId: $outlet->id,
            performerUserId: $cashier->id,
            action: 'orders.void',
            targetType: 'sales_order',
            targetId: $orderId,
            requestFingerprint: VoidCompletedOrder::approvalFingerprint($orderId, $reason),
            reason: $reason,
            idempotencyKey: 'request-approval-2',
        );

        try {
            app(ApproveSensitiveActionApproval::class)->approve($tenant->id, $approval->id, $cashier->id, 'Self approval');
            self::fail('Expected same actor approval to be rejected.');
        } catch (ApprovalException $exception) {
            self::assertSame('APPROVAL_SAME_ACTOR', $exception->errorCode());
        }

        $otherCashier = $this->tenantUser($tenant, 'other-cashier@example.com', MembershipType::Member, PredefinedRole::Cashier);

        try {
            app(ApproveSensitiveActionApproval::class)->approve($tenant->id, $approval->id, $otherCashier->id, 'Cashier approval');
            self::fail('Expected cashier approver to be rejected.');
        } catch (ApprovalException $exception) {
            self::assertSame('APPROVAL_FORBIDDEN', $exception->errorCode());
        }

        app(ApproveSensitiveActionApproval::class)->approve($tenant->id, $approval->id, $owner->id, 'Approved by owner');

        $this->withHeader('Idempotency-Key', 'void-with-mismatched-approval')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.void', [
                'outlet' => $outlet->id,
                'order' => $orderId,
            ]), [
                'reason' => 'Different reason',
                'approval_id' => $approval->id,
            ])
            ->assertConflict()
            ->assertJsonPath('code', 'APPROVAL_TARGET_MISMATCH');

        $this->assertDatabaseHas('sales_orders', [
            'id' => $orderId,
            'status' => 'completed',
        ]);
    }

    /**
     * @return array{User, User, User, Tenant, Outlet}
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
        $manager = $this->tenantUser($tenant, 'manager@example.com', MembershipType::Member, PredefinedRole::OutletManager);
        $cashier = $this->tenantUser($tenant, 'cashier@example.com', MembershipType::Member, PredefinedRole::Cashier);
        $outlet = Outlet::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Main Outlet',
            'code' => 'MAIN',
            'status' => OutletStatus::Active,
        ]);

        foreach ([$manager, $cashier] as $user) {
            OutletUserAssignment::query()->create([
                'tenant_id' => $tenant->id,
                'outlet_id' => $outlet->id,
                'user_id' => $user->id,
            ]);
        }

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

        return [$owner, $manager, $cashier, $tenant, $outlet];
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

    private function completedOrder(Tenant $tenant, Outlet $outlet, string $token): string
    {
        $category = Category::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Drinks',
            'status' => CategoryStatus::Active,
        ]);
        $product = Product::query()->create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'name' => 'Iced Tea',
            'sku' => 'TEA',
            'base_price_minor' => 13000,
            'currency' => 'IDR',
            'status' => ProductStatus::Active,
        ]);
        ProductOutletAvailability::query()->create([
            'tenant_id' => $tenant->id,
            'product_id' => $product->id,
            'outlet_id' => $outlet->id,
            'available' => true,
            'price_minor' => 13000,
        ]);

        $this->withToken($token)->postJson(route('api.v1.pos.outlets.shifts.open', [
            'outlet' => $outlet->id,
        ]), [
            'opening_cash_minor' => 50000,
        ])->assertCreated();

        $orderId = (string) $this->withHeader('Idempotency-Key', 'create-order-for-approval')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.store', ['outlet' => $outlet->id]))
            ->assertCreated()
            ->json('data.id');

        $this->withToken($token)->postJson(route('api.v1.pos.outlets.orders.items.store', [
            'outlet' => $outlet->id,
            'order' => $orderId,
        ]), [
            'product_id' => $product->id,
            'quantity' => '2',
        ])->assertOk();

        $this->withHeader('Idempotency-Key', 'complete-order-for-approval')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.complete', [
                'outlet' => $outlet->id,
                'order' => $orderId,
            ]), [
                'method' => 'cash',
                'amount_minor' => 26000,
                'currency' => 'IDR',
            ])->assertOk();

        self::assertSame('completed', Order::query()->findOrFail($orderId)->status->value);

        return $orderId;
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
