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
use App\Modules\Sales\Domain\Models\Receipt;
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

final class PosCoreReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_two_cashier_demo_path_runs_end_to_end(): void
    {
        [$owner, , $tenant, $outlet] = $this->readyOutlet('tenant-one', 'MAIN', '01k123456789abcdefghjkmnpq');
        $product = $this->product($tenant, $outlet, 'TEA', 'Iced Tea', 13000);
        $token = $this->posToken($outlet, '01k123456789abcdefghjkmnpq');

        $this->withToken($token)->getJson(route('api.v1.pos.outlets.catalog', ['outlet' => $outlet->id]))
            ->assertOk()
            ->assertJsonPath('data.0.sku', 'TEA')
            ->assertJsonPath('data.0.price_minor', 13000);

        $shiftId = (string) $this->withToken($token)->postJson(route('api.v1.pos.outlets.shifts.open', [
            'outlet' => $outlet->id,
        ]), [
            'opening_cash_minor' => 50000,
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'open')
            ->json('data.id');

        $firstCreate = $this->withHeader('Idempotency-Key', 'readiness-create-order')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.store', ['outlet' => $outlet->id]))
            ->assertCreated();
        $secondCreate = $this->withHeader('Idempotency-Key', 'readiness-create-order')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.store', ['outlet' => $outlet->id]))
            ->assertCreated();
        $orderId = (string) $firstCreate->json('data.id');
        self::assertSame($orderId, $secondCreate->json('data.id'));

        $this->withToken($token)->postJson(route('api.v1.pos.outlets.orders.items.store', [
            'outlet' => $outlet->id,
            'order' => $orderId,
        ]), [
            'product_id' => $product->id,
            'quantity' => '2',
        ])
            ->assertOk()
            ->assertJsonPath('data.total_minor', 26000)
            ->assertJsonPath('data.items.0.product_name', 'Iced Tea');

        $firstComplete = $this->withHeader('Idempotency-Key', 'readiness-complete-order')
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
        $this->withHeader('Idempotency-Key', 'readiness-complete-order')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.complete', [
                'outlet' => $outlet->id,
                'order' => $orderId,
            ]), [
                'method' => 'cash',
                'amount_minor' => 26000,
                'currency' => 'IDR',
            ])
            ->assertOk()
            ->assertJsonPath('data.id', $orderId);

        $paymentId = (string) $firstComplete->json('data.payments.0.id');
        self::assertSame(1, Order::query()->count());
        self::assertSame(1, Payment::query()->count());
        self::assertSame(1, Receipt::query()->count());

        $this->withToken($token)->getJson(route('api.v1.pos.outlets.orders.receipt', [
            'outlet' => $outlet->id,
            'order' => $orderId,
        ]))
            ->assertOk()
            ->assertJsonPath('data.payment_id', $paymentId)
            ->assertJsonPath('data.snapshot.order.total_minor', 26000)
            ->assertJsonPath('data.snapshot.payment.amount_minor', 26000);

        $this->withToken($token)->postJson(route('api.v1.pos.outlets.shifts.close', [
            'outlet' => $outlet->id,
            'shift' => $shiftId,
        ]), [
            'closing_cash_minor' => 76000,
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'closed')
            ->assertJsonPath('data.expected_cash_minor', 76000)
            ->assertJsonPath('data.gross_sales_minor', 26000);

        $this->withToken($token)->getJson(route('api.v1.pos.outlets.shifts.summary', [
            'outlet' => $outlet->id,
            'shift' => $shiftId,
        ]))
            ->assertOk()
            ->assertJsonPath('data.completed_orders_count', 1)
            ->assertJsonPath('data.gross_sales_minor', 26000)
            ->assertJsonPath('data.recorded_payments_minor', 26000)
            ->assertJsonPath('data.cash_variance_minor', 0);

        $this->withHeader('Idempotency-Key', 'readiness-create-after-close')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.store', ['outlet' => $outlet->id]))
            ->assertConflict()
            ->assertJsonPath('code', 'ORDER_ACTIVE_SHIFT_REQUIRED');

        $this->login($owner);
        $this->get(route('tenant.sales.daily', ['tenant' => $tenant->id, 'date' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('Daily Sales')
            ->assertSee('26,000.00 IDR')
            ->assertSee('Main Outlet');
    }

    public function test_pos_core_rejects_cross_outlet_and_cross_tenant_device_contexts(): void
    {
        [$tenantOneOwner, $tenantOneCashier, $tenantOne, $mainOutlet] = $this->readyOutlet('tenant-one', 'MAIN', '01k123456789abcdefghjkmnpq');
        $otherOutlet = Outlet::query()->create([
            'tenant_id' => $tenantOne->id,
            'name' => 'Other Outlet',
            'code' => 'OTHER',
            'status' => OutletStatus::Active,
        ]);
        OutletUserAssignment::query()->create([
            'tenant_id' => $tenantOne->id,
            'outlet_id' => $otherOutlet->id,
            'user_id' => $tenantOneCashier->id,
        ]);
        [, , , $foreignOutlet] = $this->readyOutlet('tenant-two', 'BRANCH', '01k987654321abcdefghjkmnpq');

        $token = $this->posToken($mainOutlet, '01k123456789abcdefghjkmnpq');

        $this->withToken($token)->postJson(route('api.v1.pos.outlets.shifts.open', [
            'outlet' => $otherOutlet->id,
        ]), [
            'opening_cash_minor' => 10000,
        ])
            ->assertNotFound()
            ->assertJsonPath('code', 'OUTLET_NOT_FOUND');

        $this->withToken($token)->getJson(route('api.v1.pos.outlets.catalog', [
            'outlet' => $foreignOutlet->id,
        ]))
            ->assertNotFound()
            ->assertJsonPath('code', 'OUTLET_NOT_FOUND');

        $this->login($tenantOneOwner);
        $this->get(route('tenant.sales.daily', ['tenant' => $foreignOutlet->tenant_id]))
            ->assertNotFound();
    }

    /**
     * @return array{User, User, Tenant, Outlet}
     */
    private function readyOutlet(string $tenantCode, string $outletCode, string $installationId): array
    {
        $tenant = Tenant::query()->create([
            'name' => str($tenantCode)->replace('-', ' ')->title()->toString(),
            'code' => $tenantCode,
            'currency' => 'IDR',
            'timezone' => 'Asia/Jakarta',
            'status' => TenantStatus::Active,
        ]);
        $owner = $this->tenantUser($tenant, $tenantCode.'-owner@example.com', MembershipType::Owner, PredefinedRole::TenantOwner);
        $cashier = $this->tenantUser($tenant, $tenantCode.'-cashier@example.com', MembershipType::Member, PredefinedRole::Cashier);
        $outlet = Outlet::query()->create([
            'tenant_id' => $tenant->id,
            'name' => $outletCode === 'MAIN' ? 'Main Outlet' : "{$outletCode} Outlet",
            'code' => $outletCode,
            'status' => OutletStatus::Active,
        ]);
        OutletUserAssignment::query()->create([
            'tenant_id' => $tenant->id,
            'outlet_id' => $outlet->id,
            'user_id' => $cashier->id,
        ]);
        PosDevice::query()->create([
            'installation_id' => $installationId,
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

    private function product(Tenant $tenant, Outlet $outlet, string $sku, string $name, int $priceMinor): Product
    {
        $category = Category::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Drinks',
            'status' => CategoryStatus::Active,
        ]);
        $product = Product::query()->create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'name' => $name,
            'sku' => $sku,
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

    private function posToken(Outlet $outlet, string $installationId): string
    {
        $tenant = Tenant::query()->findOrFail($outlet->tenant_id);
        $response = $this->postJson(route('api.v1.pos.auth.login'), [
            'email' => $tenant->code.'-cashier@example.com',
            'password' => 'password',
            'installation_id' => $installationId,
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
