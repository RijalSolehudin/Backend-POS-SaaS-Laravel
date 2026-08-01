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
use App\Modules\Sales\Domain\Models\Receipt;
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

final class ReceiptSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_order_generates_receipt_snapshot_matching_order_and_payment(): void
    {
        [$tenant, $outlet] = $this->readyOutlet();
        [$category, $product] = $this->product($tenant, $outlet, 'TEA', 'Iced Tea', 12000, 13000);
        $token = $this->posToken($outlet);
        $this->openShift($tenant, $outlet);
        [$orderId] = $this->draftOrderWithItem($token, $outlet, $product->id, '2');

        $completed = $this->withHeader('Idempotency-Key', 'complete-with-receipt')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.complete', [
                'outlet' => $outlet->id,
                'order' => $orderId,
            ]), [
                'method' => 'cash',
                'amount_minor' => 26000,
                'currency' => 'IDR',
            ])
            ->assertOk();

        $paymentId = (string) $completed->json('data.payments.0.id');

        $receipt = $this->withToken($token)->getJson(route('api.v1.pos.outlets.orders.receipt', [
            'outlet' => $outlet->id,
            'order' => $orderId,
        ]));

        $receipt
            ->assertOk()
            ->assertJsonPath('data.order_id', $orderId)
            ->assertJsonPath('data.payment_id', $paymentId)
            ->assertJsonPath('data.receipt_number', $completed->json('data.order_number'))
            ->assertJsonPath('data.snapshot.tenant.name', 'Tenant One')
            ->assertJsonPath('data.snapshot.outlet.name', 'Main Outlet')
            ->assertJsonPath('data.snapshot.outlet.code', 'MAIN')
            ->assertJsonPath('data.snapshot.order.cashier.name', 'Cashier One')
            ->assertJsonPath('data.snapshot.order.items.0.sku', 'TEA')
            ->assertJsonPath('data.snapshot.order.items.0.name', 'Iced Tea')
            ->assertJsonPath('data.snapshot.order.items.0.category_name', 'Drinks')
            ->assertJsonPath('data.snapshot.order.items.0.quantity', '2.000')
            ->assertJsonPath('data.snapshot.order.items.0.unit_price_minor', 13000)
            ->assertJsonPath('data.snapshot.order.items.0.line_subtotal_minor', 26000)
            ->assertJsonPath('data.snapshot.order.total_minor', 26000)
            ->assertJsonPath('data.snapshot.order.currency', 'IDR')
            ->assertJsonPath('data.snapshot.payment.method', 'cash')
            ->assertJsonPath('data.snapshot.payment.amount_minor', 26000)
            ->assertJsonPath('data.snapshot.payment.currency', 'IDR');

        self::assertSame(1, Receipt::query()->count());

        $tenant->forceFill(['name' => 'Tenant Renamed'])->save();
        $outlet->forceFill(['name' => 'Outlet Renamed', 'code' => 'NEW'])->save();
        $category->forceFill(['name' => 'Hot Drinks'])->save();
        $product->forceFill(['name' => 'Renamed Tea', 'base_price_minor' => 99000])->save();
        ProductOutletAvailability::query()
            ->where('product_id', $product->id)
            ->update(['price_minor' => 88000]);
        User::query()->where('email', 'cashier@example.com')->update(['name' => 'Cashier Renamed']);

        $this->withToken($token)->getJson(route('api.v1.pos.outlets.orders.receipt', [
            'outlet' => $outlet->id,
            'order' => $orderId,
        ]))
            ->assertOk()
            ->assertJsonPath('data.snapshot.tenant.name', 'Tenant One')
            ->assertJsonPath('data.snapshot.outlet.name', 'Main Outlet')
            ->assertJsonPath('data.snapshot.outlet.code', 'MAIN')
            ->assertJsonPath('data.snapshot.order.cashier.name', 'Cashier One')
            ->assertJsonPath('data.snapshot.order.items.0.name', 'Iced Tea')
            ->assertJsonPath('data.snapshot.order.items.0.category_name', 'Drinks')
            ->assertJsonPath('data.snapshot.order.items.0.unit_price_minor', 13000);
    }

    public function test_receipt_is_not_available_before_completion(): void
    {
        [$tenant, $outlet] = $this->readyOutlet();
        $token = $this->posToken($outlet);
        $this->openShift($tenant, $outlet);
        $orderId = (string) $this->withHeader('Idempotency-Key', 'create-unpaid-order')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.store', ['outlet' => $outlet->id]))
            ->assertCreated()
            ->json('data.id');

        $this->withToken($token)->getJson(route('api.v1.pos.outlets.orders.receipt', [
            'outlet' => $outlet->id,
            'order' => $orderId,
        ]))
            ->assertNotFound()
            ->assertJsonPath('code', 'RECEIPT_NOT_FOUND');
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
        $owner = $this->tenantUser($tenant, 'owner@example.com', 'Owner One', MembershipType::Owner, PredefinedRole::TenantOwner);
        $cashier = $this->tenantUser($tenant, 'cashier@example.com', 'Cashier One', MembershipType::Member, PredefinedRole::Cashier);
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

    private function tenantUser(
        Tenant $tenant,
        string $email,
        string $name,
        MembershipType $membershipType,
        PredefinedRole $role,
    ): User {
        $user = User::factory()->create(['email' => $email, 'name' => $name]);
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
     * @return array{Category, Product}
     */
    private function product(
        Tenant $tenant,
        Outlet $outlet,
        string $sku,
        string $name,
        int $basePriceMinor,
        int $outletPriceMinor,
    ): array {
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

        return [$category, $product];
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
