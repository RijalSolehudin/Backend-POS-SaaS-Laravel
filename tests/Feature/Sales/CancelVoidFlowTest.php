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
use App\Modules\Sales\Domain\Models\Order;
use App\Modules\Sales\Domain\Models\Payment;
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

final class CancelVoidFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_cancels_draft_order_idempotently_without_deleting_history(): void
    {
        [, , , $outlet] = $this->readyOutlet();
        $token = $this->posToken($outlet);
        $this->openShift($token, $outlet, 50000);
        $orderId = $this->createDraftOrder($token, $outlet, 'create-cancel-order');

        $first = $this->withHeader('Idempotency-Key', 'cancel-draft-1')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.cancel', [
                'outlet' => $outlet->id,
                'order' => $orderId,
            ]), [
                'reason' => 'Customer changed their mind',
            ]);
        $second = $this->withHeader('Idempotency-Key', 'cancel-draft-1')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.cancel', [
                'outlet' => $outlet->id,
                'order' => $orderId,
            ]), [
                'reason' => 'Customer changed their mind',
            ]);

        $first
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.cancel_reason', 'Customer changed their mind');
        $second->assertOk()->assertJsonPath('data.id', $orderId);
        self::assertSame(1, Order::query()->count());

        $this->withHeader('Idempotency-Key', 'complete-cancelled-order')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.complete', [
                'outlet' => $outlet->id,
                'order' => $orderId,
            ]), [
                'method' => 'cash',
                'amount_minor' => 0,
                'currency' => 'IDR',
            ])
            ->assertConflict()
            ->assertJsonPath('code', 'ORDER_NOT_DRAFT');

        $this->withHeader('Idempotency-Key', 'cancel-draft-1')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.cancel', [
                'outlet' => $outlet->id,
                'order' => $orderId,
            ]), [
                'reason' => 'Different reason',
            ])
            ->assertConflict()
            ->assertJsonPath('code', 'IDEMPOTENCY_CONFLICT');
    }

    public function test_tenant_owner_voids_completed_order_and_recorded_payment_with_reason(): void
    {
        [$owner, $cashier, $tenant, $outlet] = $this->readyOutlet();
        $product = $this->product($tenant, $outlet, 13000);
        $token = $this->posToken($outlet);
        $shiftId = $this->openShift($token, $outlet, 50000);
        $orderId = $this->completeOrder($token, $outlet, $product->id, '2', 'cash', 26000, 'complete-before-void');
        $paymentId = (string) Payment::query()->where('order_id', $orderId)->value('id');

        $this->login($cashier);
        $this->from(route('tenant.sales.daily', ['tenant' => $tenant->id]))
            ->post(route('tenant.sales.orders.void', ['tenant' => $tenant->id, 'order' => $orderId]), [
                'idempotency_key' => 'void-by-cashier',
                'reason' => 'Manager approval required',
            ])
            ->assertForbidden();
        $this->forgetWebSession();

        $this->login($owner);
        $this->from(route('tenant.sales.daily', ['tenant' => $tenant->id]))
            ->post(route('tenant.sales.orders.void', ['tenant' => $tenant->id, 'order' => $orderId]), [
                'idempotency_key' => 'void-completed-1',
                'reason' => 'Wrong payment method selected',
            ])
            ->assertRedirect(route('tenant.sales.daily', ['tenant' => $tenant->id]));
        $this->from(route('tenant.sales.daily', ['tenant' => $tenant->id]))
            ->post(route('tenant.sales.orders.void', ['tenant' => $tenant->id, 'order' => $orderId]), [
                'idempotency_key' => 'void-completed-1',
                'reason' => 'Wrong payment method selected',
            ])
            ->assertRedirect(route('tenant.sales.daily', ['tenant' => $tenant->id]));

        $this->assertDatabaseHas('sales_orders', [
            'id' => $orderId,
            'status' => 'voided',
            'voided_by' => $owner->id,
            'void_reason' => 'Wrong payment method selected',
        ]);
        $this->assertDatabaseHas('sales_payments', [
            'id' => $paymentId,
            'status' => 'voided',
            'voided_by' => $owner->id,
            'void_reason' => 'Wrong payment method selected',
        ]);
        self::assertSame(1, Order::query()->count());
        self::assertSame(1, Payment::query()->count());

        $this->withToken($token)->getJson(route('api.v1.pos.outlets.shifts.summary', [
            'outlet' => $outlet->id,
            'shift' => $shiftId,
        ]))
            ->assertOk()
            ->assertJsonPath('data.completed_orders_count', 0)
            ->assertJsonPath('data.gross_sales_minor', 0)
            ->assertJsonPath('data.recorded_payments_minor', 0)
            ->assertJsonPath('data.expected_cash_minor', 50000);
    }

    public function test_void_requires_reason_and_only_completed_orders_can_be_voided(): void
    {
        [$owner, , $tenant, $outlet] = $this->readyOutlet();
        $token = $this->posToken($outlet);
        $this->openShift($token, $outlet, 50000);
        $orderId = $this->createDraftOrder($token, $outlet, 'create-not-completed-order');
        $this->login($owner);

        $this->from(route('tenant.sales.daily', ['tenant' => $tenant->id]))
            ->post(route('tenant.sales.orders.void', ['tenant' => $tenant->id, 'order' => $orderId]), [
                'idempotency_key' => 'void-without-reason',
                'reason' => '',
            ])
            ->assertSessionHasErrors('reason');

        $this->from(route('tenant.sales.daily', ['tenant' => $tenant->id]))
            ->post(route('tenant.sales.orders.void', ['tenant' => $tenant->id, 'order' => $orderId]), [
                'idempotency_key' => 'void-draft-order',
                'reason' => 'Wrong order',
            ])
            ->assertRedirect(route('tenant.sales.daily', ['tenant' => $tenant->id]))
            ->assertSessionHasErrors('reason');
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

    private function product(Tenant $tenant, Outlet $outlet, int $priceMinor): Product
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
            'base_price_minor' => $priceMinor,
            'currency' => 'IDR',
            'status' => ProductStatus::Active,
        ]);
        ProductOutletAvailability::query()->create([
            'tenant_id' => $tenant->id,
            'product_id' => $product->id,
            'outlet_id' => $outlet->id,
            'available' => true,
            'price_minor' => $priceMinor,
        ]);

        return $product;
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

    private function createDraftOrder(string $token, Outlet $outlet, string $idempotencyKey): string
    {
        return (string) $this->withHeader('Idempotency-Key', $idempotencyKey)
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.store', ['outlet' => $outlet->id]))
            ->assertCreated()
            ->json('data.id');
    }

    private function completeOrder(
        string $token,
        Outlet $outlet,
        string $productId,
        string $quantity,
        string $method,
        int $amountMinor,
        string $idempotencyKey,
    ): string {
        $orderId = $this->createDraftOrder($token, $outlet, 'create-'.$idempotencyKey);

        $this->withToken($token)->postJson(route('api.v1.pos.outlets.orders.items.store', [
            'outlet' => $outlet->id,
            'order' => $orderId,
        ]), [
            'product_id' => $productId,
            'quantity' => $quantity,
        ])->assertOk();

        $this->withHeader('Idempotency-Key', $idempotencyKey)
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.complete', [
                'outlet' => $outlet->id,
                'order' => $orderId,
            ]), [
                'method' => $method,
                'amount_minor' => $amountMinor,
                'currency' => 'IDR',
            ])->assertOk();

        return $orderId;
    }

    private function login(User $user): void
    {
        $this->actingAs($user, 'web')->withSession([
            'tenant.authenticated_at' => now()->getTimestamp(),
            'tenant.last_activity_at' => now()->getTimestamp(),
        ]);
    }

    private function forgetWebSession(): void
    {
        $this->app['auth']->guard('web')->forgetUser();
        $this->flushSession();
    }
}
