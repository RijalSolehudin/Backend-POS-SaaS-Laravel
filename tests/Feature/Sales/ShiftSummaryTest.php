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

final class ShiftSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_shift_summary_matches_completed_orders_and_recorded_payments(): void
    {
        [$owner, , $tenant, $outlet] = $this->readyOutlet();
        $product = $this->product($tenant, $outlet, 13000);
        $token = $this->posToken($outlet);
        $shiftId = $this->openShift($token, $outlet, 50000);
        $this->completeOrder($token, $outlet, $product->id, '2', 'cash', 26000, 'complete-cash');
        $this->completeOrder($token, $outlet, $product->id, '1', 'manual_non_cash', 13000, 'complete-noncash');

        $this->withToken($token)->getJson(route('api.v1.pos.outlets.shifts.summary', [
            'outlet' => $outlet->id,
            'shift' => $shiftId,
        ]))
            ->assertOk()
            ->assertJsonPath('data.completed_orders_count', 2)
            ->assertJsonPath('data.gross_sales_minor', 39000)
            ->assertJsonPath('data.recorded_payments_minor', 39000)
            ->assertJsonPath('data.cash_payments_minor', 26000)
            ->assertJsonPath('data.manual_non_cash_payments_minor', 13000)
            ->assertJsonPath('data.expected_cash_minor', 76000)
            ->assertJsonPath('data.cash_variance_minor', 0);

        $this->withHeader('Idempotency-Key', 'close-summary-shift')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.shifts.close', [
                'outlet' => $outlet->id,
                'shift' => $shiftId,
            ]), [
                'closing_cash_minor' => 75000,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'closed')
            ->assertJsonPath('data.expected_cash_minor', 76000)
            ->assertJsonPath('data.gross_sales_minor', 39000);

        $this->withToken($token)->getJson(route('api.v1.pos.outlets.shifts.summary', [
            'outlet' => $outlet->id,
            'shift' => $shiftId,
        ]))
            ->assertOk()
            ->assertJsonPath('data.closing_cash_minor', 75000)
            ->assertJsonPath('data.cash_variance_minor', -1000);

        $this->withHeader('Idempotency-Key', 'closed-shift-order')
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.store', ['outlet' => $outlet->id]))
            ->assertConflict()
            ->assertJsonPath('code', 'ORDER_ACTIVE_SHIFT_REQUIRED');

        $this->login($owner);
        $this->get(route('tenant.sales.daily', ['tenant' => $tenant->id, 'date' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('Daily Sales')
            ->assertSee('39,000.00 IDR')
            ->assertSee('26,000.00 IDR')
            ->assertSee('13,000.00 IDR');
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

    private function completeOrder(
        string $token,
        Outlet $outlet,
        string $productId,
        string $quantity,
        string $method,
        int $amountMinor,
        string $idempotencyKey,
    ): void {
        $orderId = (string) $this->withHeader('Idempotency-Key', 'create-'.$idempotencyKey)
            ->withToken($token)
            ->postJson(route('api.v1.pos.outlets.orders.store', ['outlet' => $outlet->id]))
            ->assertCreated()
            ->json('data.id');

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
    }

    private function login(User $user): void
    {
        $this->actingAs($user, 'web')->withSession([
            'tenant.authenticated_at' => now()->getTimestamp(),
            'tenant.last_activity_at' => now()->getTimestamp(),
        ]);
    }
}
