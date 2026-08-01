<?php

declare(strict_types=1);

namespace Tests\Feature\Recipe;

use App\Modules\Catalog\Domain\Enums\CategoryStatus;
use App\Modules\Catalog\Domain\Enums\ProductStatus;
use App\Modules\Catalog\Domain\Models\Category;
use App\Modules\Catalog\Domain\Models\Product;
use App\Modules\Catalog\Domain\Models\ProductVariant;
use App\Modules\Identity\Domain\Enums\PredefinedRole;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Identity\Domain\Models\UserRoleAssignment;
use App\Modules\Inventory\Application\Actions\RecordOpeningBalance;
use App\Modules\Inventory\Application\Data\OpeningBalanceInput;
use App\Modules\Inventory\Domain\Enums\InventoryStatus;
use App\Modules\Inventory\Domain\Enums\StockMovementType;
use App\Modules\Inventory\Domain\Models\InventoryBalance;
use App\Modules\Inventory\Domain\Models\InventoryItem;
use App\Modules\Inventory\Domain\Models\InventoryStockMovement;
use App\Modules\Inventory\Domain\Models\InventoryUnit;
use App\Modules\Recipe\Application\Actions\ActivateRecipeVersion;
use App\Modules\Recipe\Application\Actions\CreateRecipe;
use App\Modules\Recipe\Application\Actions\CreateRecipeVersion;
use App\Modules\Recipe\Application\Actions\SetRecipeVariantMapping;
use App\Modules\Recipe\Application\Data\RecipeIngredientInput;
use App\Modules\Recipe\Application\Data\RecipeInput;
use App\Modules\Recipe\Application\Data\RecipeVersionInput;
use App\Modules\Recipe\Application\Exceptions\RecipeException;
use App\Modules\Recipe\Domain\Enums\RecipeStatus;
use App\Modules\Recipe\Domain\Enums\RecipeVersionStatus;
use App\Modules\Recipe\Domain\Models\Recipe;
use App\Modules\Recipe\Domain\Models\RecipeIngredient;
use App\Modules\Recipe\Domain\Models\RecipeSalesDeduction;
use App\Modules\Recipe\Domain\Models\RecipeVariantMapping;
use App\Modules\Sales\Application\Actions\CompleteOrderWithPayment;
use App\Modules\Sales\Domain\Enums\OrderStatus;
use App\Modules\Sales\Domain\Enums\PaymentMethod;
use App\Modules\Sales\Domain\Enums\ShiftStatus;
use App\Modules\Sales\Domain\Models\Order;
use App\Modules\Sales\Domain\Models\OrderItem;
use App\Modules\Sales\Domain\Models\Shift;
use App\Modules\Tenancy\Application\Data\PosOutletContext;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Domain\Enums\MembershipType;
use App\Modules\Tenancy\Domain\Enums\OutletStatus;
use App\Modules\Tenancy\Domain\Enums\TenantStatus;
use App\Modules\Tenancy\Domain\Models\Outlet;
use App\Modules\Tenancy\Domain\Models\Tenant;
use App\Modules\Tenancy\Domain\Models\TenantMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RecipePhaseSixTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_manages_recipe_header(): void
    {
        $tenant = $this->tenant();
        $owner = $this->user('owner@example.com', $tenant);
        $this->login($owner);

        $this->get(route('tenant.recipes.index', ['tenant' => $tenant->id]))
            ->assertOk()
            ->assertSee('Recipes');

        $this->post(route('tenant.recipes.store', ['tenant' => $tenant->id]), [
            'name' => 'Iced Latte Recipe',
            'sku' => 'latte-r',
            'requires_recipe' => '1',
        ])->assertRedirect();

        $recipe = Recipe::query()->where('tenant_id', $tenant->id)->firstOrFail();

        self::assertSame('LATTE-R', $recipe->sku);
        self::assertSame(RecipeStatus::Active, $recipe->status);
        self::assertTrue($recipe->requires_recipe);
    }

    public function test_recipe_version_costing_activation_and_variant_mapping(): void
    {
        $tenant = $this->tenant();
        $owner = $this->user('owner@example.com', $tenant);
        $outlet = $this->outlet($tenant, 'MAIN');
        $unit = $this->unit($tenant);
        $item = $this->inventoryItem($tenant, $unit, 'MILK');
        $variant = $this->catalogVariant($tenant);
        $context = new TenantRequestContext($tenant->id, $owner->id, MembershipType::Owner);
        $this->openingBalance($context, $outlet, $item, '10.000', 100000, 'opening-milk');

        $recipe = $this->app->make(CreateRecipe::class)->handle($context, new RecipeInput('Latte', 'LATTE', true));
        $version = $this->app->make(CreateRecipeVersion::class)->handle(
            $context,
            new RecipeVersionInput(
                recipeId: $recipe->id,
                yieldQuantity: '1.000',
                yieldPercent: 100,
                currency: 'IDR',
                ingredients: [new RecipeIngredientInput($item->id, '0.250')],
            ),
            $outlet->id,
        );

        self::assertSame(2500, $version->cost_minor);
        self::assertSame(2500, RecipeIngredient::query()->firstOrFail()->total_cost_minor_snapshot);

        $active = $this->app->make(ActivateRecipeVersion::class)->handle($context, $version->id);
        $mapping = $this->app->make(SetRecipeVariantMapping::class)->handle($context, $variant->id, $active->id, true);

        self::assertSame(RecipeVersionStatus::Active, $active->status);
        self::assertSame($active->id, $mapping->recipe_version_id);
        self::assertTrue($mapping->requires_recipe);
    }

    public function test_sales_completion_deducts_recipe_inventory_and_replays_idempotently(): void
    {
        $tenant = $this->tenant();
        $cashier = $this->user('cashier@example.com', $tenant);
        $outlet = $this->outlet($tenant, 'MAIN');
        $unit = $this->unit($tenant);
        $item = $this->inventoryItem($tenant, $unit, 'BEANS');
        $variant = $this->catalogVariant($tenant);
        $tenantContext = new TenantRequestContext($tenant->id, $cashier->id, MembershipType::Owner);
        $this->openingBalance($tenantContext, $outlet, $item, '1.000', 100000, 'opening-beans');
        $recipe = $this->app->make(CreateRecipe::class)->handle($tenantContext, new RecipeInput('Coffee', 'COFFEE', true));
        $version = $this->app->make(CreateRecipeVersion::class)->handle(
            $tenantContext,
            new RecipeVersionInput($recipe->id, '1.000', 100, 'IDR', [new RecipeIngredientInput($item->id, '0.250')]),
            $outlet->id,
        );
        $active = $this->app->make(ActivateRecipeVersion::class)->handle($tenantContext, $version->id);
        $this->app->make(SetRecipeVariantMapping::class)->handle($tenantContext, $variant->id, $active->id, true);
        $shift = $this->shift($tenant, $outlet, $cashier);
        $order = $this->order($tenant, $outlet, $cashier, $shift, 40000);
        $this->orderItem($tenant, $order, $variant, '2.000', 40000);
        $posContext = new PosOutletContext($tenant->id, $outlet->id, 'device-1', $cashier->id);

        $completed = $this->app->make(CompleteOrderWithPayment::class)->handle(
            $posContext,
            $order->id,
            PaymentMethod::Cash,
            40000,
            'IDR',
            'complete-coffee-1',
        );
        $retry = $this->app->make(CompleteOrderWithPayment::class)->handle(
            $posContext,
            $order->id,
            PaymentMethod::Cash,
            40000,
            'IDR',
            'complete-coffee-1',
        );

        self::assertSame(OrderStatus::Completed, $completed->status);
        self::assertSame($completed->id, $retry->id);
        self::assertSame(1, RecipeSalesDeduction::query()->count());
        self::assertSame(1, InventoryStockMovement::query()->where('movement_type', StockMovementType::SalesDeduction)->count());
        self::assertSame('0.500', InventoryBalance::query()->where('item_id', $item->id)->firstOrFail()->quantity);
    }

    public function test_sales_completion_rejects_recipe_with_insufficient_stock(): void
    {
        $tenant = $this->tenant();
        $cashier = $this->user('cashier@example.com', $tenant);
        $outlet = $this->outlet($tenant, 'MAIN');
        $unit = $this->unit($tenant);
        $item = $this->inventoryItem($tenant, $unit, 'SYRUP');
        $variant = $this->catalogVariant($tenant);
        $tenantContext = new TenantRequestContext($tenant->id, $cashier->id, MembershipType::Owner);
        $this->openingBalance($tenantContext, $outlet, $item, '0.100', 10000, 'opening-syrup');
        $recipe = $this->app->make(CreateRecipe::class)->handle($tenantContext, new RecipeInput('Sweet Coffee', 'SWEET-COFFEE', true));
        $version = $this->app->make(CreateRecipeVersion::class)->handle(
            $tenantContext,
            new RecipeVersionInput($recipe->id, '1.000', 100, 'IDR', [new RecipeIngredientInput($item->id, '0.250')]),
            $outlet->id,
        );
        $active = $this->app->make(ActivateRecipeVersion::class)->handle($tenantContext, $version->id);
        $this->app->make(SetRecipeVariantMapping::class)->handle($tenantContext, $variant->id, $active->id, true);
        $shift = $this->shift($tenant, $outlet, $cashier);
        $order = $this->order($tenant, $outlet, $cashier, $shift, 20000);
        $this->orderItem($tenant, $order, $variant, '1.000', 20000);

        $this->expectException(RecipeException::class);
        $this->expectExceptionMessage('Recipe stock deduction cannot be completed because inventory stock is insufficient.');

        $this->app->make(CompleteOrderWithPayment::class)->handle(
            new PosOutletContext($tenant->id, $outlet->id, 'device-1', $cashier->id),
            $order->id,
            PaymentMethod::Cash,
            20000,
            'IDR',
            'complete-insufficient-recipe',
        );
    }

    public function test_sales_completion_rejects_required_recipe_mapping_without_version(): void
    {
        $tenant = $this->tenant();
        $cashier = $this->user('cashier@example.com', $tenant);
        $outlet = $this->outlet($tenant, 'MAIN');
        $variant = $this->catalogVariant($tenant);
        RecipeVariantMapping::query()->create([
            'tenant_id' => $tenant->id,
            'variant_id' => $variant->id,
            'requires_recipe' => true,
        ]);
        $shift = $this->shift($tenant, $outlet, $cashier);
        $order = $this->order($tenant, $outlet, $cashier, $shift, 10000);
        $this->orderItem($tenant, $order, $variant, '1.000', 10000);

        $this->expectException(RecipeException::class);
        $this->expectExceptionMessage('A recipe mapping is required before completing this sales item.');

        $this->app->make(CompleteOrderWithPayment::class)->handle(
            new PosOutletContext($tenant->id, $outlet->id, 'device-1', $cashier->id),
            $order->id,
            PaymentMethod::Cash,
            10000,
            'IDR',
            'complete-missing-recipe',
        );
    }

    private function tenant(): Tenant
    {
        return Tenant::query()->create([
            'name' => 'Acme POS',
            'code' => 'acme-pos',
            'status' => TenantStatus::Active,
            'currency' => 'IDR',
            'timezone' => 'Asia/Jakarta',
        ]);
    }

    private function user(string $email, Tenant $tenant): User
    {
        $user = User::factory()->create(['email' => $email]);
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'membership_type' => MembershipType::Owner,
        ]);
        UserRoleAssignment::query()->create(['user_id' => $user->id, 'role' => PredefinedRole::TenantOwner]);

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

    private function unit(Tenant $tenant): InventoryUnit
    {
        return InventoryUnit::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Kilogram',
            'symbol' => 'kg',
            'precision' => 3,
            'status' => InventoryStatus::Active,
        ]);
    }

    private function inventoryItem(Tenant $tenant, InventoryUnit $unit, string $sku): InventoryItem
    {
        return InventoryItem::query()->create([
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'name' => $sku,
            'sku' => $sku,
            'status' => InventoryStatus::Active,
        ]);
    }

    private function openingBalance(TenantRequestContext $context, Outlet $outlet, InventoryItem $item, string $quantity, int $cost, string $key): void
    {
        $this->app->make(RecordOpeningBalance::class)->handle(
            $context,
            new OpeningBalanceInput($outlet->id, $item->id, $quantity, $cost, 'IDR', 'Initial stock'),
            $key,
        );
    }

    private function catalogVariant(Tenant $tenant): ProductVariant
    {
        $category = Category::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Drinks',
            'display_order' => 0,
            'status' => CategoryStatus::Active,
        ]);
        $product = Product::query()->create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'name' => 'Coffee',
            'sku' => 'COFFEE',
            'base_price_minor' => 20000,
            'currency' => 'IDR',
            'display_order' => 0,
            'status' => ProductStatus::Active,
        ]);

        return ProductVariant::query()->create([
            'tenant_id' => $tenant->id,
            'product_id' => $product->id,
            'name' => 'Regular',
            'sku' => 'COFFEE-REG',
            'price_minor' => 20000,
            'currency' => 'IDR',
            'is_default' => true,
            'display_order' => 0,
            'status' => ProductStatus::Active,
        ]);
    }

    private function shift(Tenant $tenant, Outlet $outlet, User $user): Shift
    {
        return Shift::query()->create([
            'tenant_id' => $tenant->id,
            'outlet_id' => $outlet->id,
            'user_id' => $user->id,
            'status' => ShiftStatus::Open,
            'opening_cash_minor' => 0,
            'expected_cash_minor' => 0,
            'gross_sales_minor' => 0,
            'currency' => 'IDR',
            'opened_at' => now(),
        ]);
    }

    private function order(Tenant $tenant, Outlet $outlet, User $user, Shift $shift, int $totalMinor): Order
    {
        return Order::query()->create([
            'tenant_id' => $tenant->id,
            'outlet_id' => $outlet->id,
            'shift_id' => $shift->id,
            'user_id' => $user->id,
            'order_number' => 'ORD-'.$totalMinor,
            'status' => OrderStatus::Draft,
            'subtotal_minor' => $totalMinor,
            'discount_minor' => 0,
            'service_charge_minor' => 0,
            'tax_minor' => 0,
            'total_minor' => $totalMinor,
            'currency' => 'IDR',
        ]);
    }

    private function orderItem(Tenant $tenant, Order $order, ProductVariant $variant, string $quantity, int $subtotalMinor): OrderItem
    {
        return OrderItem::query()->create([
            'tenant_id' => $tenant->id,
            'order_id' => $order->id,
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'product_sku' => 'COFFEE',
            'variant_sku' => $variant->sku,
            'product_name' => 'Coffee',
            'variant_name' => $variant->name,
            'product_category_id' => Category::query()->where('tenant_id', $tenant->id)->firstOrFail()->id,
            'product_category_name' => 'Drinks',
            'quantity' => $quantity,
            'unit_price_minor' => 20000,
            'modifier_total_minor' => 0,
            'line_subtotal_minor' => $subtotalMinor,
            'currency' => 'IDR',
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
