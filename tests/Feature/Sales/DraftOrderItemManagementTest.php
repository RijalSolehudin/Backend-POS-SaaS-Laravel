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

final class DraftOrderItemManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_creates_draft_order_and_manages_snapshot_items(): void
    {
        [$tenant, $outlet] = $this->readyOutlet();
        $product = $this->product($tenant, $outlet, 'TEA', 'Iced Tea', 12000, 13000);
        $token = $this->posToken($outlet);
        $this->openShift($tenant, $outlet);

        $created = $this->withHeader('Idempotency-Key', 'create-order-1')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.store', ['outlet' => $outlet->id]));

        $created
            ->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.subtotal_minor', 0)
            ->assertJsonPath('data.total_minor', 0)
            ->assertJsonPath('data.currency', 'IDR')
            ->assertJsonCount(0, 'data.items');

        $orderId = (string) $created->json('data.id');

        $added = $this->withToken($token)->postJson(route('api.v1.pos.outlets.orders.items.store', [
            'outlet' => $outlet->id,
            'order' => $orderId,
        ]), [
            'product_id' => $product->id,
            'quantity' => '2',
        ]);

        $added
            ->assertOk()
            ->assertJsonPath('data.items.0.product_sku', 'TEA')
            ->assertJsonPath('data.items.0.product_name', 'Iced Tea')
            ->assertJsonPath('data.items.0.product_category_name', 'Drinks')
            ->assertJsonPath('data.items.0.quantity', '2.000')
            ->assertJsonPath('data.items.0.unit_price_minor', 13000)
            ->assertJsonPath('data.items.0.line_subtotal_minor', 26000)
            ->assertJsonPath('data.subtotal_minor', 26000)
            ->assertJsonPath('data.total_minor', 26000);

        $itemId = (string) $added->json('data.items.0.id');

        $this->withToken($token)->putJson(route('api.v1.pos.outlets.orders.items.update', [
            'outlet' => $outlet->id,
            'order' => $orderId,
            'item' => $itemId,
        ]), [
            'quantity' => '1.500',
        ])
            ->assertOk()
            ->assertJsonPath('data.items.0.quantity', '1.500')
            ->assertJsonPath('data.items.0.line_subtotal_minor', 19500)
            ->assertJsonPath('data.total_minor', 19500);

        $this->withToken($token)->deleteJson(route('api.v1.pos.outlets.orders.items.destroy', [
            'outlet' => $outlet->id,
            'order' => $orderId,
            'item' => $itemId,
        ]))
            ->assertOk()
            ->assertJsonCount(0, 'data.items')
            ->assertJsonPath('data.total_minor', 0);
    }

    public function test_retry_create_draft_order_with_same_idempotency_key_returns_existing_order(): void
    {
        [$tenant, $outlet] = $this->readyOutlet();
        $token = $this->posToken($outlet);
        $this->openShift($tenant, $outlet);

        $first = $this->withHeader('Idempotency-Key', 'create-order-retry')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.store', ['outlet' => $outlet->id]));
        $second = $this->withHeader('Idempotency-Key', 'create-order-retry')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.store', ['outlet' => $outlet->id]));

        $first->assertCreated();
        $second->assertCreated()->assertJsonPath('data.id', $first->json('data.id'));
        self::assertSame(1, Order::query()->count());
    }

    public function test_create_draft_order_requires_open_shift_and_idempotency_key(): void
    {
        [, $outlet] = $this->readyOutlet();
        $token = $this->posToken($outlet);

        $this->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.store', ['outlet' => $outlet->id]))
            ->assertUnprocessable()
            ->assertJsonPath('code', 'IDEMPOTENCY_KEY_REQUIRED');

        $this->withHeader('Idempotency-Key', 'create-order-without-shift')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.store', ['outlet' => $outlet->id]))
            ->assertConflict()
            ->assertJsonPath('code', 'ORDER_ACTIVE_SHIFT_REQUIRED');
    }

    public function test_unavailable_product_is_rejected_and_existing_snapshot_survives_master_price_change(): void
    {
        [$tenant, $outlet] = $this->readyOutlet();
        $visible = $this->product($tenant, $outlet, 'TEA', 'Iced Tea', 12000, 13000);
        $hidden = $this->product($tenant, $outlet, 'HIDE', 'Hidden Tea', 15000, 15000, false);
        $token = $this->posToken($outlet);
        $this->openShift($tenant, $outlet);
        $orderId = (string) $this->withHeader('Idempotency-Key', 'create-order-snapshot')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.store', ['outlet' => $outlet->id]))
            ->json('data.id');

        $this->withToken($token)->postJson(route('api.v1.pos.outlets.orders.items.store', [
            'outlet' => $outlet->id,
            'order' => $orderId,
        ]), [
            'product_id' => $visible->id,
            'quantity' => '1',
        ])->assertOk();

        $visible->forceFill(['name' => 'Renamed Tea', 'base_price_minor' => 99000])->save();
        ProductOutletAvailability::query()
            ->where('product_id', $visible->id)
            ->update(['price_minor' => 88000]);

        $this->withToken($token)->getJson(route('api.v1.pos.outlets.orders.show', [
            'outlet' => $outlet->id,
            'order' => $orderId,
        ]))
            ->assertOk()
            ->assertJsonPath('data.items.0.product_name', 'Iced Tea')
            ->assertJsonPath('data.items.0.product_category_name', 'Drinks')
            ->assertJsonPath('data.items.0.unit_price_minor', 13000);

        $this->withToken($token)->postJson(route('api.v1.pos.outlets.orders.items.store', [
            'outlet' => $outlet->id,
            'order' => $orderId,
        ]), [
            'product_id' => $hidden->id,
            'quantity' => '1',
        ])
            ->assertNotFound()
            ->assertJsonPath('code', 'ORDER_PRODUCT_UNAVAILABLE');
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
        bool $available = true,
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
            'available' => $available,
            'price_minor' => $outletPriceMinor,
        ]);

        return $product;
    }

    private function openShift(Tenant $tenant, Outlet $outlet): Shift
    {
        $cashier = User::query()->where('email', 'cashier@example.com')->firstOrFail();

        return Shift::query()->create([
            'tenant_id' => $tenant->id,
            'outlet_id' => $outlet->id,
            'user_id' => $cashier->id,
            'open_shift_key' => $tenant->id.':'.$outlet->id.':'.$cashier->id,
            'status' => ShiftStatus::Open,
            'opened_at' => now(),
            'opening_cash_minor' => 50000,
            'expected_cash_minor' => 50000,
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
}
