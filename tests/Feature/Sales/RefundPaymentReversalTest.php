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
use App\Modules\Sales\Application\Actions\RecordFullRefund;
use App\Modules\Sales\Application\Actions\RequestSensitiveActionApproval;
use App\Modules\Sales\Domain\Models\Payment;
use App\Modules\Sales\Domain\Models\Receipt;
use App\Modules\Sales\Domain\Models\Refund;
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

final class RefundPaymentReversalTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_records_full_manual_refund_with_supervisor_approval_idempotently(): void
    {
        [$owner, $cashier, $tenant, $outlet] = $this->readyOutlet();
        $token = $this->posToken($outlet);
        [$shiftId, $orderId] = $this->completedOrder($tenant, $outlet, $token);
        $paymentId = (string) Payment::query()->where('order_id', $orderId)->value('id');
        $reason = 'Customer returned full order';

        $this->withHeader('Idempotency-Key', 'refund-missing-approval')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.refund', [
                'outlet' => $outlet->id,
                'order' => $orderId,
            ]), [
                'amount_minor' => 26000,
                'currency' => 'IDR',
                'reason' => $reason,
                'approval_id' => '01k123456789abcdefghjkmnpq',
            ])
            ->assertNotFound()
            ->assertJsonPath('code', 'APPROVAL_NOT_FOUND');

        $approval = app(RequestSensitiveActionApproval::class)->handle(
            tenantId: $tenant->id,
            outletId: $outlet->id,
            performerUserId: $cashier->id,
            action: 'payments.refund',
            targetType: 'sales_order',
            targetId: $orderId,
            requestFingerprint: RecordFullRefund::approvalFingerprint($orderId, 26000, 'IDR', $reason),
            reason: $reason,
            idempotencyKey: 'refund-approval-1',
        );
        app(ApproveSensitiveActionApproval::class)->approve($tenant->id, $approval->id, $owner->id, 'Approved full refund');

        $first = $this->withHeader('Idempotency-Key', 'refund-order-1')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.refund', [
                'outlet' => $outlet->id,
                'order' => $orderId,
            ]), [
                'amount_minor' => 26000,
                'currency' => 'IDR',
                'reason' => $reason,
                'approval_id' => $approval->id,
            ]);
        $second = $this->withHeader('Idempotency-Key', 'refund-order-1')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.refund', [
                'outlet' => $outlet->id,
                'order' => $orderId,
            ]), [
                'amount_minor' => 26000,
                'currency' => 'IDR',
                'reason' => $reason,
                'approval_id' => $approval->id,
            ]);

        $first
            ->assertCreated()
            ->assertJsonPath('data.status', 'recorded')
            ->assertJsonPath('data.amount_minor', 26000)
            ->assertJsonPath('data.payment_id', $paymentId);
        $second
            ->assertCreated()
            ->assertJsonPath('data.id', $first->json('data.id'));

        self::assertSame(1, Refund::query()->count());
        $this->assertDatabaseHas('sales_payments', [
            'id' => $paymentId,
            'status' => 'recorded',
        ]);
        $this->assertDatabaseHas('sales_receipts', [
            'order_id' => $orderId,
            'payment_id' => $paymentId,
        ]);
        self::assertSame(1, Receipt::query()->count());
        $this->assertDatabaseHas('sales_sensitive_action_approvals', [
            'id' => $approval->id,
            'status' => 'consumed',
        ]);
        $this->assertDatabaseHas('sales_audit_events', [
            'event_type' => 'payment.refunded',
            'target_id' => $first->json('data.id'),
        ]);

        $this->withToken($token)->getJson(route('api.v1.pos.outlets.shifts.summary', [
            'outlet' => $outlet->id,
            'shift' => $shiftId,
        ]))
            ->assertOk()
            ->assertJsonPath('data.gross_sales_minor', 26000)
            ->assertJsonPath('data.refunds_minor', 26000)
            ->assertJsonPath('data.net_sales_minor', 0)
            ->assertJsonPath('data.expected_cash_minor', 50000);

        $this->login($owner);
        $this->get(route('tenant.sales.daily', ['tenant' => $tenant->id]))
            ->assertOk()
            ->assertSee('Refunds')
            ->assertSee('Net Sales')
            ->assertSee('26,000.00 IDR');
    }

    public function test_refund_rejects_mismatched_amount_currency_duplicate_and_idempotency_conflict(): void
    {
        [$owner, $cashier, $tenant, $outlet] = $this->readyOutlet();
        $token = $this->posToken($outlet);
        [, $orderId] = $this->completedOrder($tenant, $outlet, $token);
        $reason = 'Customer returned full order';
        $approval = $this->approvedRefund($tenant, $outlet, $cashier, $owner, $orderId, 26000, 'IDR', $reason, 'refund-approval-2');

        $this->withHeader('Idempotency-Key', 'refund-wrong-amount')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.refund', [
                'outlet' => $outlet->id,
                'order' => $orderId,
            ]), [
                'amount_minor' => 13000,
                'currency' => 'IDR',
                'reason' => $reason,
                'approval_id' => $approval,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'REFUND_AMOUNT_MISMATCH');

        $this->withHeader('Idempotency-Key', 'refund-wrong-currency')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.refund', [
                'outlet' => $outlet->id,
                'order' => $orderId,
            ]), [
                'amount_minor' => 26000,
                'currency' => 'USD',
                'reason' => $reason,
                'approval_id' => $approval,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'REFUND_CURRENCY_MISMATCH');

        $this->withHeader('Idempotency-Key', 'refund-conflict')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.refund', [
                'outlet' => $outlet->id,
                'order' => $orderId,
            ]), [
                'amount_minor' => 26000,
                'currency' => 'IDR',
                'reason' => $reason,
                'approval_id' => $approval,
            ])
            ->assertCreated();
        $this->withHeader('Idempotency-Key', 'refund-conflict')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.refund', [
                'outlet' => $outlet->id,
                'order' => $orderId,
            ]), [
                'amount_minor' => 26000,
                'currency' => 'IDR',
                'reason' => 'Different refund reason',
                'approval_id' => $approval,
            ])
            ->assertConflict()
            ->assertJsonPath('code', 'IDEMPOTENCY_CONFLICT');

        $secondApproval = $this->approvedRefund($tenant, $outlet, $cashier, $owner, $orderId, 26000, 'IDR', $reason, 'refund-approval-3');
        $this->withHeader('Idempotency-Key', 'refund-duplicate')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.refund', [
                'outlet' => $outlet->id,
                'order' => $orderId,
            ]), [
                'amount_minor' => 26000,
                'currency' => 'IDR',
                'reason' => $reason,
                'approval_id' => $secondApproval,
            ])
            ->assertConflict()
            ->assertJsonPath('code', 'REFUND_ALREADY_RECORDED');
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

    /**
     * @return array{string, string}
     */
    private function completedOrder(Tenant $tenant, Outlet $outlet, string $token): array
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

        $shiftId = (string) $this->withToken($token)->postJson(route('api.v1.pos.outlets.shifts.open', [
            'outlet' => $outlet->id,
        ]), [
            'opening_cash_minor' => 50000,
        ])->assertCreated()->json('data.id');
        $orderId = (string) $this->withHeader('Idempotency-Key', 'create-order-for-refund')
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

        $this->withHeader('Idempotency-Key', 'complete-order-for-refund')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.complete', [
                'outlet' => $outlet->id,
                'order' => $orderId,
            ]), [
                'method' => 'cash',
                'amount_minor' => 26000,
                'currency' => 'IDR',
            ])->assertOk();

        return [$shiftId, $orderId];
    }

    private function approvedRefund(
        Tenant $tenant,
        Outlet $outlet,
        User $cashier,
        User $owner,
        string $orderId,
        int $amountMinor,
        string $currency,
        string $reason,
        string $idempotencyKey,
    ): string {
        $approval = app(RequestSensitiveActionApproval::class)->handle(
            tenantId: $tenant->id,
            outletId: $outlet->id,
            performerUserId: $cashier->id,
            action: 'payments.refund',
            targetType: 'sales_order',
            targetId: $orderId,
            requestFingerprint: RecordFullRefund::approvalFingerprint($orderId, $amountMinor, $currency, $reason),
            reason: $reason,
            idempotencyKey: $idempotencyKey,
        );
        app(ApproveSensitiveActionApproval::class)->approve($tenant->id, $approval->id, $owner->id, 'Approved full refund');

        return $approval->id;
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

    private function login(User $user): void
    {
        $this->actingAs($user, 'web')->withSession([
            'tenant.authenticated_at' => now()->getTimestamp(),
            'tenant.last_activity_at' => now()->getTimestamp(),
        ]);
    }
}
