<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Modules\Identity\Domain\Enums\PredefinedRole;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Identity\Domain\Models\UserRoleAssignment;
use App\Modules\Inventory\Application\Actions\RecordStockMovement;
use App\Modules\Inventory\Application\Data\StockMovementInput;
use App\Modules\Inventory\Domain\Enums\InventoryStatus;
use App\Modules\Inventory\Domain\Enums\StockMovementType;
use App\Modules\Inventory\Domain\Models\InventoryAuditEvent;
use App\Modules\Inventory\Domain\Models\InventoryBalance;
use App\Modules\Inventory\Domain\Models\InventoryItem;
use App\Modules\Inventory\Domain\Models\InventoryItemOutletSetting;
use App\Modules\Inventory\Domain\Models\InventoryStockMovement;
use App\Modules\Inventory\Domain\Models\InventoryUnit;
use App\Modules\Tenancy\Domain\Enums\MembershipType;
use App\Modules\Tenancy\Domain\Enums\OutletStatus;
use App\Modules\Tenancy\Domain\Enums\TenantStatus;
use App\Modules\Tenancy\Domain\Models\Outlet;
use App\Modules\Tenancy\Domain\Models\Tenant;
use App\Modules\Tenancy\Domain\Models\TenantMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class InventoryModuleFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_manages_inventory_units_items_and_outlet_settings(): void
    {
        $tenant = $this->tenant();
        $owner = $this->user('owner@example.com', $tenant, MembershipType::Owner, PredefinedRole::TenantOwner);
        $outlet = $this->outlet($tenant, 'MAIN');
        $this->login($owner);

        $this->get(route('tenant.inventory.index', ['tenant' => $tenant->id]))
            ->assertOk()
            ->assertSee('Inventory')
            ->assertSee('Units')
            ->assertSee('Items');

        $this->post(route('tenant.inventory.units.store', ['tenant' => $tenant->id]), [
            'name' => 'Kilogram',
            'symbol' => 'KG',
            'precision' => 3,
        ])->assertRedirect();

        $unit = InventoryUnit::query()->where('tenant_id', $tenant->id)->firstOrFail();

        self::assertSame('kg', $unit->symbol);
        self::assertSame(InventoryStatus::Active, $unit->status);

        $this->post(route('tenant.inventory.items.store', ['tenant' => $tenant->id]), [
            'name' => 'Coffee Beans',
            'sku' => ' beans-01 ',
            'unit_id' => $unit->id,
        ])->assertRedirect();

        $item = InventoryItem::query()->where('tenant_id', $tenant->id)->firstOrFail();

        self::assertSame('BEANS-01', $item->sku);
        self::assertSame($unit->id, $item->unit_id);

        $this->put(route('tenant.inventory.items.outlet-settings', [
            'tenant' => $tenant->id,
            'item' => $item->id,
        ]), [
            'outlet_id' => $outlet->id,
            'status' => 'inactive',
            'low_stock_threshold_quantity' => '2.5',
        ])->assertRedirect();

        $setting = InventoryItemOutletSetting::query()
            ->where('tenant_id', $tenant->id)
            ->where('item_id', $item->id)
            ->where('outlet_id', $outlet->id)
            ->firstOrFail();

        self::assertSame(InventoryStatus::Inactive, $setting->status);
        self::assertSame('2.500', $setting->low_stock_threshold_quantity);

        $this->post(route('tenant.inventory.items.status', [
            'tenant' => $tenant->id,
            'item' => $item->id,
        ]), [
            'status' => 'inactive',
        ])->assertRedirect();

        self::assertSame(InventoryStatus::Inactive, $item->refresh()->status);
        self::assertGreaterThanOrEqual(4, InventoryAuditEvent::query()->where('tenant_id', $tenant->id)->count());
    }

    public function test_duplicate_item_sku_is_rejected_within_the_same_tenant(): void
    {
        $tenant = $this->tenant();
        $owner = $this->user('owner@example.com', $tenant, MembershipType::Owner, PredefinedRole::TenantOwner);
        $unit = $this->unit($tenant);
        $this->item($tenant, $unit, 'MILK-01', 'Milk');
        $this->login($owner);

        $this->from(route('tenant.inventory.index', ['tenant' => $tenant->id]))
            ->post(route('tenant.inventory.items.store', ['tenant' => $tenant->id]), [
                'name' => 'Fresh Milk',
                'sku' => ' milk-01 ',
                'unit_id' => $unit->id,
            ])
            ->assertRedirect(route('tenant.inventory.index', ['tenant' => $tenant->id]))
            ->assertSessionHasErrors('sku');
    }

    public function test_cross_tenant_unit_and_outlet_references_are_rejected(): void
    {
        $tenant = $this->tenant();
        $otherTenant = $this->tenant('Other Tenant', 'other');
        $owner = $this->user('owner@example.com', $tenant, MembershipType::Owner, PredefinedRole::TenantOwner);
        $otherUnit = $this->unit($otherTenant);
        $unit = $this->unit($tenant, 'Piece', 'pcs');
        $item = $this->item($tenant, $unit, 'SUGAR-01', 'Sugar');
        $otherOutlet = $this->outlet($otherTenant, 'OTHER');
        $this->login($owner);

        $this->from(route('tenant.inventory.index', ['tenant' => $tenant->id]))
            ->post(route('tenant.inventory.items.store', ['tenant' => $tenant->id]), [
                'name' => 'Cross Tenant Item',
                'sku' => 'CROSS-01',
                'unit_id' => $otherUnit->id,
            ])
            ->assertRedirect(route('tenant.inventory.index', ['tenant' => $tenant->id]))
            ->assertSessionHasErrors('unit_id');

        $this->from(route('tenant.inventory.index', ['tenant' => $tenant->id]))
            ->put(route('tenant.inventory.items.outlet-settings', [
                'tenant' => $tenant->id,
                'item' => $item->id,
            ]), [
                'outlet_id' => $otherOutlet->id,
                'status' => 'active',
                'low_stock_threshold_quantity' => '1.000',
            ])
            ->assertRedirect(route('tenant.inventory.index', ['tenant' => $tenant->id]))
            ->assertSessionHasErrors('outlet_id');
    }

    public function test_non_owner_cannot_access_inventory_admin(): void
    {
        $tenant = $this->tenant();
        $manager = $this->user('manager@example.com', $tenant, MembershipType::Member, PredefinedRole::OutletManager);
        $this->login($manager);

        $this->get(route('tenant.inventory.index', ['tenant' => $tenant->id]))
            ->assertForbidden();
    }

    public function test_opening_balance_records_movement_and_balance_once(): void
    {
        $tenant = $this->tenant();
        $owner = $this->user('owner@example.com', $tenant, MembershipType::Owner, PredefinedRole::TenantOwner);
        $outlet = $this->outlet($tenant, 'MAIN');
        $unit = $this->unit($tenant);
        $item = $this->item($tenant, $unit, 'BEANS-01', 'Coffee Beans');
        $this->login($owner);

        $this->withHeader('Idempotency-Key', 'opening-beans-1')
            ->post(route('tenant.inventory.items.opening-balances.store', [
                'tenant' => $tenant->id,
                'item' => $item->id,
            ]), [
                'outlet_id' => $outlet->id,
                'quantity' => '10.500',
                'total_cost_minor' => 210000,
                'currency' => 'IDR',
                'reason' => 'Initial count',
            ])->assertRedirect();

        $movement = InventoryStockMovement::query()->firstOrFail();
        $balance = InventoryBalance::query()->firstOrFail();

        self::assertSame(StockMovementType::OpeningBalance, $movement->movement_type);
        self::assertSame('10.500', $movement->quantity);
        self::assertSame(20000, $movement->unit_cost_minor);
        self::assertSame(210000, $movement->total_cost_minor);
        self::assertSame('10.500', $movement->balance_quantity_after);
        self::assertSame('10.500', $balance->quantity);
        self::assertSame(210000, $balance->total_cost_minor);

        self::assertFalse($movement->forceFill(['reason' => 'Edited'])->save());
        self::assertFalse($movement->delete());
    }

    public function test_opening_balance_idempotency_replays_and_rejects_payload_conflict(): void
    {
        $tenant = $this->tenant();
        $owner = $this->user('owner@example.com', $tenant, MembershipType::Owner, PredefinedRole::TenantOwner);
        $outlet = $this->outlet($tenant, 'MAIN');
        $unit = $this->unit($tenant);
        $item = $this->item($tenant, $unit, 'MILK-01', 'Milk');
        $this->login($owner);

        $payload = [
            'outlet_id' => $outlet->id,
            'quantity' => '4.000',
            'total_cost_minor' => 80000,
            'currency' => 'IDR',
            'reason' => 'Initial count',
        ];

        $route = route('tenant.inventory.items.opening-balances.store', [
            'tenant' => $tenant->id,
            'item' => $item->id,
        ]);

        $this->withHeader('Idempotency-Key', 'opening-milk-1')
            ->post($route, $payload)
            ->assertRedirect();

        $this->withHeader('Idempotency-Key', 'opening-milk-1')
            ->post($route, $payload)
            ->assertRedirect();

        self::assertSame(1, InventoryStockMovement::query()->count());
        self::assertSame(1, InventoryBalance::query()->count());

        $this->withHeader('Idempotency-Key', 'opening-milk-1')
            ->from(route('tenant.inventory.index', ['tenant' => $tenant->id]))
            ->post($route, [...$payload, 'quantity' => '5.000'])
            ->assertRedirect(route('tenant.inventory.index', ['tenant' => $tenant->id]))
            ->assertSessionHasErrors('idempotency_key');
    }

    public function test_second_opening_balance_and_negative_stock_are_rejected(): void
    {
        $tenant = $this->tenant();
        $owner = $this->user('owner@example.com', $tenant, MembershipType::Owner, PredefinedRole::TenantOwner);
        $outlet = $this->outlet($tenant, 'MAIN');
        $unit = $this->unit($tenant);
        $item = $this->item($tenant, $unit, 'SUGAR-01', 'Sugar');
        $this->login($owner);

        $route = route('tenant.inventory.items.opening-balances.store', [
            'tenant' => $tenant->id,
            'item' => $item->id,
        ]);

        $this->withHeader('Idempotency-Key', 'opening-sugar-1')
            ->post($route, [
                'outlet_id' => $outlet->id,
                'quantity' => '1.000',
                'total_cost_minor' => 10000,
                'currency' => 'IDR',
                'reason' => 'Initial count',
            ])
            ->assertRedirect();

        $this->withHeader('Idempotency-Key', 'opening-sugar-2')
            ->from(route('tenant.inventory.index', ['tenant' => $tenant->id]))
            ->post($route, [
                'outlet_id' => $outlet->id,
                'quantity' => '1.000',
                'total_cost_minor' => 10000,
                'currency' => 'IDR',
                'reason' => 'Second count',
            ])
            ->assertRedirect(route('tenant.inventory.index', ['tenant' => $tenant->id]))
            ->assertSessionHasErrors('idempotency_key');

        $this->expectExceptionMessage('The stock mutation would make the inventory balance negative.');

        $this->app->make(RecordStockMovement::class)->handle(new StockMovementInput(
            tenantId: $tenant->id,
            outletId: $outlet->id,
            itemId: $item->id,
            unitId: $unit->id,
            actorUserId: $owner->id,
            movementType: StockMovementType::AdjustmentDecrease,
            sourceType: 'test',
            sourceId: null,
            quantity: '-2.000',
            unitCostMinor: null,
            totalCostMinor: null,
            currency: 'IDR',
            reason: 'Negative stock check',
            idempotencyKey: 'negative-stock-1',
        ));
    }

    private function tenant(string $name = 'Acme POS', string $code = 'acme-pos'): Tenant
    {
        return Tenant::query()->create([
            'name' => $name,
            'code' => $code,
            'status' => TenantStatus::Active,
            'currency' => 'IDR',
            'timezone' => 'Asia/Jakarta',
        ]);
    }

    private function user(
        string $email,
        Tenant $tenant,
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

    private function unit(Tenant $tenant, string $name = 'Kilogram', string $symbol = 'kg'): InventoryUnit
    {
        return InventoryUnit::query()->create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'symbol' => $symbol,
            'precision' => 3,
            'status' => InventoryStatus::Active,
        ]);
    }

    private function item(Tenant $tenant, InventoryUnit $unit, string $sku, string $name): InventoryItem
    {
        return InventoryItem::query()->create([
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'name' => $name,
            'sku' => $sku,
            'status' => InventoryStatus::Active,
        ]);
    }

    private function login(User $user): void
    {
        $this->actingAs($user, 'web')->withSession([
            'tenant.authenticated_at' => now()->getTimestamp(),
            'tenant.last_activity_at' => now()->getTimestamp(),
        ]);
    }
}
