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
use App\Modules\Sales\Domain\Enums\ShiftStatus;
use App\Modules\Sales\Domain\Models\Order;
use App\Modules\Sales\Domain\Models\Payment;
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

final class PaymentCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_completes_order_with_full_cash_payment_idempotently(): void
    {
        [$tenant, $outlet] = $this->readyOutlet();
        $product = $this->product($tenant, $outlet, 'TEA', 'Iced Tea', 12000, 13000);
        $token = $this->posToken($outlet);
        $shift = $this->openShift($tenant, $outlet, 50000);
        [$orderId] = $this->draftOrderWithItem($token, $outlet, $product->id, '2');

        $first = $this->withHeader('Idempotency-Key', 'complete-order-1')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.complete', [
                'outlet' => $outlet->id,
                'order' => $orderId,
            ]), [
                'method' => 'cash',
                'amount_minor' => 26000,
                'currency' => 'IDR',
            ]);

        $second = $this->withHeader('Idempotency-Key', 'complete-order-1')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.complete', [
                'outlet' => $outlet->id,
                'order' => $orderId,
            ]), [
                'method' => 'cash',
                'amount_minor' => 26000,
                'currency' => 'IDR',
            ]);

        $first
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.total_minor', 26000)
            ->assertJsonPath('data.payments.0.method', 'cash')
            ->assertJsonPath('data.payments.0.status', 'recorded')
            ->assertJsonPath('data.payments.0.amount_minor', 26000)
            ->assertJsonPath('data.payments.0.currency', 'IDR');
        $second->assertOk()->assertJsonPath('data.id', $first->json('data.id'));
        self::assertSame(1, Payment::query()->count());
        self::assertSame(1, Order::query()->where('status', 'completed')->count());

        $shift->refresh();
        self::assertSame(76000, $shift->expected_cash_minor);
        self::assertSame(26000, $shift->gross_sales_minor);
    }

    public function test_payment_amount_and_currency_must_match_order_total(): void
    {
        [$tenant, $outlet] = $this->readyOutlet();
        $product = $this->product($tenant, $outlet, 'TEA', 'Iced Tea', 12000, 13000);
        $token = $this->posToken($outlet);
        $this->openShift($tenant, $outlet);
        [$orderId] = $this->draftOrderWithItem($token, $outlet, $product->id, '1');

        $this->withHeader('Idempotency-Key', 'complete-order-wrong-amount')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.complete', [
                'outlet' => $outlet->id,
                'order' => $orderId,
            ]), [
                'method' => 'cash',
                'amount_minor' => 12000,
                'currency' => 'IDR',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'PAYMENT_AMOUNT_MISMATCH');

        $this->withHeader('Idempotency-Key', 'complete-order-wrong-currency')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.complete', [
                'outlet' => $outlet->id,
                'order' => $orderId,
            ]), [
                'method' => 'cash',
                'amount_minor' => 13000,
                'currency' => 'USD',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'PAYMENT_CURRENCY_MISMATCH');

        self::assertSame(0, Payment::query()->count());
    }

    public function test_empty_draft_order_cannot_be_completed(): void
    {
        [$tenant, $outlet] = $this->readyOutlet();
        $token = $this->posToken($outlet);
        $this->openShift($tenant, $outlet);
        $orderId = (string) $this->withHeader('Idempotency-Key', 'create-empty-order')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.store', ['outlet' => $outlet->id]))
            ->assertCreated()
            ->json('data.id');

        $this->withHeader('Idempotency-Key', 'complete-empty-order')
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
            ->assertJsonPath('code', 'ORDER_ITEMS_REQUIRED');

        self::assertSame(0, Payment::query()->count());
    }

    public function test_completed_order_is_immutable_and_idempotency_conflict_is_rejected(): void
    {
        [$tenant, $outlet] = $this->readyOutlet();
        $product = $this->product($tenant, $outlet, 'TEA', 'Iced Tea', 12000, 13000);
        $token = $this->posToken($outlet);
        $this->openShift($tenant, $outlet);
        [$orderId, $itemId] = $this->draftOrderWithItem($token, $outlet, $product->id, '1');

        $this->withHeader('Idempotency-Key', 'complete-order-conflict')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.complete', [
                'outlet' => $outlet->id,
                'order' => $orderId,
            ]), [
                'method' => 'manual_non_cash',
                'amount_minor' => 13000,
                'currency' => 'IDR',
            ])
            ->assertOk();

        $this->withToken($token)->putJson(route('api.v1.pos.outlets.orders.items.update', [
            'outlet' => $outlet->id,
            'order' => $orderId,
            'item' => $itemId,
        ]), [
            'quantity' => '2',
        ])
            ->assertConflict()
            ->assertJsonPath('code', 'ORDER_NOT_DRAFT');

        $this->withHeader('Idempotency-Key', 'complete-order-conflict')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.complete', [
                'outlet' => $outlet->id,
                'order' => $orderId,
            ]), [
                'method' => 'cash',
                'amount_minor' => 13000,
                'currency' => 'IDR',
            ])
            ->assertConflict()
            ->assertJsonPath('code', 'IDEMPOTENCY_CONFLICT');

        self::assertSame(1, Payment::query()->count());
    }

    /**
     * @return array{Tenant, Outlet}
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

        return [$tenant, $outlet];
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

    private function product(
        Tenant $tenant,
        Outlet $outlet,
        string $sku,
        string $name,
        int $basePriceMinor,
        int $outletPriceMinor,
    ): Product {
        $category = Category::query()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Drinks'],
            ['status' => CategoryStatus::Active],
        );
        $product = Product::query()->create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'name' => $name,
            'sku' => $sku,
            'base_price_minor' => $basePriceMinor,
            'currency' => 'IDR',
            'status' => ProductStatus::Active,
        ]);
        ProductOutletAvailability::query()->create([
            'tenant_id' => $tenant->id,
            'product_id' => $product->id,
            'outlet_id' => $outlet->id,
            'available' => true,
            'price_minor' => $outletPriceMinor,
        ]);

        return $product;
    }

    private function openShift(Tenant $tenant, Outlet $outlet, int $openingCashMinor = 50000): Shift
    {
        $cashier = User::query()->where('email', 'cashier@example.com')->firstOrFail();

        return Shift::query()->create([
            'tenant_id' => $tenant->id,
            'outlet_id' => $outlet->id,
            'user_id' => $cashier->id,
            'open_shift_key' => $tenant->id.':'.$outlet->id.':'.$cashier->id,
            'status' => ShiftStatus::Open,
            'opened_at' => now(),
            'opening_cash_minor' => $openingCashMinor,
            'expected_cash_minor' => $openingCashMinor,
            'gross_sales_minor' => 0,
            'currency' => 'IDR',
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

    /**
     * @return array{string, string}
     */
    private function draftOrderWithItem(string $token, Outlet $outlet, string $productId, string $quantity): array
    {
        $orderId = (string) $this->withHeader('Idempotency-Key', 'create-order-'.$productId.'-'.$quantity)
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.store', ['outlet' => $outlet->id]))
            ->assertCreated()
            ->json('data.id');

        $itemId = (string) $this->withToken($token)->postJson(route('api.v1.pos.outlets.orders.items.store', [
            'outlet' => $outlet->id,
            'order' => $orderId,
        ]), [
            'product_id' => $productId,
            'quantity' => $quantity,
        ])
            ->assertOk()
            ->json('data.items.0.id');

        return [$orderId, $itemId];
    }
}
