<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Modules\Catalog\Domain\Enums\CategoryStatus;
use App\Modules\Catalog\Domain\Enums\ProductStatus;
use App\Modules\Catalog\Domain\Models\Category;
use App\Modules\Catalog\Domain\Models\ModifierGroup;
use App\Modules\Catalog\Domain\Models\ModifierOption;
use App\Modules\Catalog\Domain\Models\ModifierOptionOutletOverride;
use App\Modules\Catalog\Domain\Models\Product;
use App\Modules\Catalog\Domain\Models\ProductOutletAvailability;
use App\Modules\Catalog\Domain\Models\ProductVariant;
use App\Modules\Catalog\Domain\Models\VariantOutletAvailability;
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

final class MinimumCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_manages_minimum_catalog_and_flutter_reads_available_products(): void
    {
        $tenant = $this->tenant();
        $owner = $this->user('owner@example.com', $tenant, MembershipType::Owner, PredefinedRole::TenantOwner);
        $cashier = $this->user('cashier@example.com', $tenant, MembershipType::Member, PredefinedRole::Cashier);
        $outlet = $this->outlet($tenant, 'MAIN');
        $this->assignOutlet($tenant, $outlet, $cashier);
        $this->device($tenant, $outlet, '01k123456789abcdefghjkmnpq', $owner);
        $this->login($owner);

        $this->get(route('tenant.catalog.index', ['tenant' => $tenant->id]))
            ->assertOk()
            ->assertSee('Catalog')
            ->assertSee('Categories')
            ->assertSee('Products');

        $this->post(route('tenant.catalog.categories.store', ['tenant' => $tenant->id]), [
            'name' => 'Drinks',
        ])->assertRedirect();

        $category = Category::query()->where('tenant_id', $tenant->id)->firstOrFail();

        $this->post(route('tenant.catalog.products.store', ['tenant' => $tenant->id]), [
            'name' => 'Iced Tea',
            'sku' => 'tea-ice',
            'category_id' => $category->id,
            'base_price_minor' => 12000,
            'currency' => 'IDR',
        ])->assertRedirect();

        $product = Product::query()->where('tenant_id', $tenant->id)->firstOrFail();

        $this->put(route('tenant.catalog.products.availability', [
            'tenant' => $tenant->id,
            'product' => $product->id,
        ]), [
            'outlet_id' => $outlet->id,
            'available' => '1',
            'price_minor' => 13000,
        ])->assertRedirect();

        $token = $this->posToken($outlet);
        $this->forgetWebSession();

        $this->withToken($token)
            ->getJson(route('api.v1.pos.outlets.catalog', ['outlet' => $outlet->id]))
            ->assertOk()
            ->assertJsonPath('data.0.sku', 'TEA-ICE')
            ->assertJsonPath('data.0.price_minor', 13000)
            ->assertJsonPath('data.0.currency', 'IDR')
            ->assertJsonPath('data.0.category.name', 'Drinks');
    }

    public function test_duplicate_sku_is_rejected_within_the_same_tenant(): void
    {
        $tenant = $this->tenant();
        $owner = $this->user('owner@example.com', $tenant, MembershipType::Owner, PredefinedRole::TenantOwner);
        $category = $this->category($tenant, 'Food');
        $this->product($tenant, $category, 'NASI-1', 'Nasi Goreng');
        $this->login($owner);

        $this->from(route('tenant.catalog.index', ['tenant' => $tenant->id]))
            ->post(route('tenant.catalog.products.store', ['tenant' => $tenant->id]), [
                'name' => 'Nasi Goreng Special',
                'sku' => 'nasi-1',
                'category_id' => $category->id,
                'base_price_minor' => 25000,
                'currency' => 'IDR',
            ])
            ->assertRedirect(route('tenant.catalog.index', ['tenant' => $tenant->id]))
            ->assertSessionHasErrors('sku');
    }

    public function test_cross_tenant_category_reference_is_rejected(): void
    {
        $tenant = $this->tenant();
        $otherTenant = $this->tenant('Other Tenant', 'other');
        $owner = $this->user('owner@example.com', $tenant, MembershipType::Owner, PredefinedRole::TenantOwner);
        $otherCategory = $this->category($otherTenant, 'Other Food');
        $this->login($owner);

        $this->from(route('tenant.catalog.index', ['tenant' => $tenant->id]))
            ->post(route('tenant.catalog.products.store', ['tenant' => $tenant->id]), [
                'name' => 'Cross Tenant Product',
                'sku' => 'CROSS-1',
                'category_id' => $otherCategory->id,
                'base_price_minor' => 20000,
                'currency' => 'IDR',
            ])
            ->assertRedirect(route('tenant.catalog.index', ['tenant' => $tenant->id]))
            ->assertSessionHasErrors('category_id');

        $this->assertDatabaseMissing('catalog_products', ['sku' => 'CROSS-1']);
    }

    public function test_flutter_catalog_hides_inactive_and_unavailable_products(): void
    {
        $tenant = $this->tenant();
        $owner = $this->user('owner@example.com', $tenant, MembershipType::Owner, PredefinedRole::TenantOwner);
        $cashier = $this->user('cashier@example.com', $tenant, MembershipType::Member, PredefinedRole::Cashier);
        $outlet = $this->outlet($tenant, 'MAIN');
        $this->assignOutlet($tenant, $outlet, $cashier);
        $this->device($tenant, $outlet, '01k123456789abcdefghjkmnpq', $owner);
        $category = $this->category($tenant, 'Food');
        $visible = $this->product($tenant, $category, 'VISIBLE', 'Visible Product');
        $inactive = $this->product($tenant, $category, 'INACTIVE', 'Inactive Product', ProductStatus::Inactive);
        $unavailable = $this->product($tenant, $category, 'UNAVAILABLE', 'Unavailable Product');
        $this->availability($tenant, $outlet, $visible, true);
        $this->availability($tenant, $outlet, $inactive, true);
        $this->availability($tenant, $outlet, $unavailable, false);

        $response = $this->withToken($this->posToken($outlet))
            ->getJson(route('api.v1.pos.outlets.catalog', ['outlet' => $outlet->id]));

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.sku', 'VISIBLE');
    }

    public function test_flutter_catalog_uses_category_hierarchy_and_display_order(): void
    {
        $tenant = $this->tenant();
        $owner = $this->user('owner@example.com', $tenant, MembershipType::Owner, PredefinedRole::TenantOwner);
        $cashier = $this->user('cashier@example.com', $tenant, MembershipType::Member, PredefinedRole::Cashier);
        $outlet = $this->outlet($tenant, 'MAIN');
        $this->assignOutlet($tenant, $outlet, $cashier);
        $this->device($tenant, $outlet, '01k123456789abcdefghjkmnpq', $owner);

        $inactiveParent = $this->category($tenant, 'Inactive Parent', null, 1, CategoryStatus::Inactive);
        $hiddenChild = $this->category($tenant, 'Hidden Child', $inactiveParent, 1);
        $food = $this->category($tenant, 'Food', null, 20);
        $drinks = $this->category($tenant, 'Drinks', null, 10);
        $coffee = $this->category($tenant, 'Coffee', $drinks, 5);

        $hidden = $this->product($tenant, $hiddenChild, 'HIDDEN', 'Hidden Product');
        $second = $this->product($tenant, $food, 'SECOND', 'Second Product', ProductStatus::Active, 20);
        $first = $this->product($tenant, $coffee, 'FIRST', 'First Product', ProductStatus::Active, 5);
        $this->availability($tenant, $outlet, $hidden, true);
        $this->availability($tenant, $outlet, $second, true);
        $this->availability($tenant, $outlet, $first, true);

        $response = $this->withToken($this->posToken($outlet))
            ->getJson(route('api.v1.pos.outlets.catalog', ['outlet' => $outlet->id]));

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.sku', 'FIRST')
            ->assertJsonPath('data.0.category.name', 'Coffee')
            ->assertJsonPath('data.0.category.parent.name', 'Drinks')
            ->assertJsonPath('data.1.sku', 'SECOND');
    }

    public function test_owner_manages_sellable_variants_and_flutter_reads_active_variants(): void
    {
        $tenant = $this->tenant();
        $owner = $this->user('owner@example.com', $tenant, MembershipType::Owner, PredefinedRole::TenantOwner);
        $cashier = $this->user('cashier@example.com', $tenant, MembershipType::Member, PredefinedRole::Cashier);
        $outlet = $this->outlet($tenant, 'MAIN');
        $this->assignOutlet($tenant, $outlet, $cashier);
        $this->device($tenant, $outlet, '01k123456789abcdefghjkmnpq', $owner);
        $category = $this->category($tenant, 'Drinks');
        $product = $this->product($tenant, $category, 'LATTE', 'Latte');
        $this->availability($tenant, $outlet, $product, true);
        $this->login($owner);

        $this->post(route('tenant.catalog.products.variants.store', [
            'tenant' => $tenant->id,
            'product' => $product->id,
        ]), [
            'name' => 'Regular',
            'sku' => 'latte-regular',
            'price_minor' => 22000,
            'currency' => 'IDR',
            'is_default' => '1',
            'display_order' => 20,
        ])->assertRedirect();

        $this->post(route('tenant.catalog.products.variants.store', [
            'tenant' => $tenant->id,
            'product' => $product->id,
        ]), [
            'name' => 'Large',
            'sku' => 'latte-large',
            'price_minor' => 28000,
            'currency' => 'IDR',
            'display_order' => 10,
        ])->assertRedirect();

        $inactive = $this->variant($tenant, $product, 'LATTE-INACTIVE', 'Inactive', ProductStatus::Inactive);
        self::assertSame(ProductStatus::Inactive, $inactive->status);

        $this->forgetWebSession();

        $response = $this->withToken($this->posToken($outlet))
            ->getJson(route('api.v1.pos.outlets.catalog', ['outlet' => $outlet->id]));

        $response
            ->assertOk()
            ->assertJsonPath('data.0.variants.0.sku', 'LATTE-REGULAR')
            ->assertJsonPath('data.0.variants.0.is_default', true)
            ->assertJsonPath('data.0.variants.0.price_minor', 22000)
            ->assertJsonPath('data.0.variants.1.sku', 'LATTE-LARGE')
            ->assertJsonCount(2, 'data.0.variants');
    }

    public function test_flutter_catalog_reads_modifier_groups_and_active_options_for_variants(): void
    {
        $tenant = $this->tenant();
        $owner = $this->user('owner@example.com', $tenant, MembershipType::Owner, PredefinedRole::TenantOwner);
        $cashier = $this->user('cashier@example.com', $tenant, MembershipType::Member, PredefinedRole::Cashier);
        $outlet = $this->outlet($tenant, 'MAIN');
        $this->assignOutlet($tenant, $outlet, $cashier);
        $this->device($tenant, $outlet, '01k123456789abcdefghjkmnpq', $owner);
        $category = $this->category($tenant, 'Drinks');
        $product = $this->product($tenant, $category, 'MATCHA', 'Matcha');
        $variant = $this->variant($tenant, $product, 'MATCHA-LARGE', 'Large');
        $this->availability($tenant, $outlet, $product, true);

        $milk = $this->modifierGroup($tenant, $product, 'Milk', true, 1, 1, 10);
        $this->modifierOption($tenant, $milk, 'Oat Milk', 5000, ProductStatus::Active, 10);
        $this->modifierOption($tenant, $milk, 'Almond Milk', 6000, ProductStatus::Inactive, 20);

        $topping = $this->modifierGroup($tenant, $product, 'Topping', false, 0, 2, 20, $variant);
        $this->modifierOption($tenant, $topping, 'Pearl', 4000);

        $response = $this->withToken($this->posToken($outlet))
            ->getJson(route('api.v1.pos.outlets.catalog', ['outlet' => $outlet->id]));

        $response
            ->assertOk()
            ->assertJsonPath('data.0.variants.0.modifier_groups.0.name', 'Milk')
            ->assertJsonPath('data.0.variants.0.modifier_groups.0.required', true)
            ->assertJsonPath('data.0.variants.0.modifier_groups.0.min_selection', 1)
            ->assertJsonPath('data.0.variants.0.modifier_groups.0.max_selection', 1)
            ->assertJsonPath('data.0.variants.0.modifier_groups.0.options.0.name', 'Oat Milk')
            ->assertJsonPath('data.0.variants.0.modifier_groups.0.options.0.price_delta_minor', 5000)
            ->assertJsonPath('data.0.variants.0.modifier_groups.1.name', 'Topping')
            ->assertJsonPath('data.0.variants.0.modifier_groups.1.options.0.name', 'Pearl')
            ->assertJsonCount(1, 'data.0.variants.0.modifier_groups.0.options');
    }

    public function test_flutter_catalog_resolves_outlet_variant_and_modifier_overrides(): void
    {
        $tenant = $this->tenant();
        $owner = $this->user('owner@example.com', $tenant, MembershipType::Owner, PredefinedRole::TenantOwner);
        $cashier = $this->user('cashier@example.com', $tenant, MembershipType::Member, PredefinedRole::Cashier);
        $mainOutlet = $this->outlet($tenant, 'MAIN');
        $otherOutlet = $this->outlet($tenant, 'OTHER');
        $this->assignOutlet($tenant, $mainOutlet, $cashier);
        $this->device($tenant, $mainOutlet, '01k123456789abcdefghjkmnpq', $owner);
        $category = $this->category($tenant, 'Drinks');
        $product = $this->product($tenant, $category, 'COFFEE', 'Coffee');
        $regular = $this->variant($tenant, $product, 'COFFEE-REG', 'Regular', ProductStatus::Active, 10);
        $hiddenLarge = $this->variant($tenant, $product, 'COFFEE-LARGE', 'Large', ProductStatus::Active, 20);
        $this->availability($tenant, $mainOutlet, $product, true);
        $this->availability($tenant, $otherOutlet, $product, true);
        $this->variantAvailability($tenant, $mainOutlet, $regular, true, 18000);
        $this->variantAvailability($tenant, $mainOutlet, $hiddenLarge, false);

        $milk = $this->modifierGroup($tenant, $product, 'Milk', false, 0, 1);
        $oat = $this->modifierOption($tenant, $milk, 'Oat Milk', 5000);
        $almond = $this->modifierOption($tenant, $milk, 'Almond Milk', 6000);
        $this->modifierOptionOverride($tenant, $mainOutlet, $oat, true, 7000);
        $this->modifierOptionOverride($tenant, $mainOutlet, $almond, false);

        $response = $this->withToken($this->posToken($mainOutlet))
            ->getJson(route('api.v1.pos.outlets.catalog', ['outlet' => $mainOutlet->id]));

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data.0.variants')
            ->assertJsonPath('data.0.variants.0.sku', 'COFFEE-REG')
            ->assertJsonPath('data.0.variants.0.price_minor', 18000)
            ->assertJsonCount(1, 'data.0.variants.0.modifier_groups.0.options')
            ->assertJsonPath('data.0.variants.0.modifier_groups.0.options.0.name', 'Oat Milk')
            ->assertJsonPath('data.0.variants.0.modifier_groups.0.options.0.price_delta_minor', 7000);
    }

    private function tenant(string $name = 'Tenant One', string $code = 'tenant-one'): Tenant
    {
        return Tenant::query()->create([
            'name' => $name,
            'code' => $code,
            'currency' => 'IDR',
            'timezone' => 'Asia/Jakarta',
            'status' => TenantStatus::Active,
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

    private function assignOutlet(Tenant $tenant, Outlet $outlet, User $user): void
    {
        OutletUserAssignment::query()->create([
            'tenant_id' => $tenant->id,
            'outlet_id' => $outlet->id,
            'user_id' => $user->id,
        ]);
    }

    private function device(Tenant $tenant, Outlet $outlet, string $installationId, User $registeredBy): PosDevice
    {
        return PosDevice::query()->create([
            'installation_id' => $installationId,
            'tenant_id' => $tenant->id,
            'outlet_id' => $outlet->id,
            'name' => 'Front Counter',
            'client_type' => 'pos_terminal',
            'platform' => 'android',
            'status' => PosDeviceStatus::Active,
            'registered_by' => $registeredBy->id,
        ]);
    }

    private function category(
        Tenant $tenant,
        string $name,
        ?Category $parent = null,
        int $displayOrder = 0,
        CategoryStatus $status = CategoryStatus::Active,
    ): Category {
        return Category::query()->create([
            'tenant_id' => $tenant->id,
            'parent_id' => $parent?->id,
            'name' => $name,
            'display_order' => $displayOrder,
            'status' => $status,
        ]);
    }

    private function product(
        Tenant $tenant,
        Category $category,
        string $sku,
        string $name,
        ProductStatus $status = ProductStatus::Active,
        int $displayOrder = 0,
    ): Product {
        return Product::query()->create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'name' => $name,
            'sku' => $sku,
            'base_price_minor' => 10000,
            'currency' => 'IDR',
            'display_order' => $displayOrder,
            'status' => $status,
        ]);
    }

    private function availability(Tenant $tenant, Outlet $outlet, Product $product, bool $available): void
    {
        ProductOutletAvailability::query()->create([
            'tenant_id' => $tenant->id,
            'product_id' => $product->id,
            'outlet_id' => $outlet->id,
            'available' => $available,
        ]);
    }

    private function variant(
        Tenant $tenant,
        Product $product,
        string $sku,
        string $name,
        ProductStatus $status = ProductStatus::Active,
        int $displayOrder = 0,
    ): ProductVariant {
        return ProductVariant::query()->create([
            'tenant_id' => $tenant->id,
            'product_id' => $product->id,
            'name' => $name,
            'sku' => $sku,
            'price_minor' => 10000,
            'currency' => 'IDR',
            'is_default' => false,
            'display_order' => $displayOrder,
            'status' => $status,
        ]);
    }

    private function modifierGroup(
        Tenant $tenant,
        Product $product,
        string $name,
        bool $required,
        int $minSelection,
        int $maxSelection,
        int $displayOrder = 0,
        ?ProductVariant $variant = null,
    ): ModifierGroup {
        return ModifierGroup::query()->create([
            'tenant_id' => $tenant->id,
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'name' => $name,
            'required' => $required,
            'min_selection' => $minSelection,
            'max_selection' => $maxSelection,
            'display_order' => $displayOrder,
            'status' => ProductStatus::Active,
        ]);
    }

    private function modifierOption(
        Tenant $tenant,
        ModifierGroup $group,
        string $name,
        int $priceDeltaMinor,
        ProductStatus $status = ProductStatus::Active,
        int $displayOrder = 0,
    ): ModifierOption {
        return ModifierOption::query()->create([
            'tenant_id' => $tenant->id,
            'group_id' => $group->id,
            'name' => $name,
            'price_delta_minor' => $priceDeltaMinor,
            'currency' => 'IDR',
            'display_order' => $displayOrder,
            'status' => $status,
        ]);
    }

    private function variantAvailability(
        Tenant $tenant,
        Outlet $outlet,
        ProductVariant $variant,
        bool $available,
        ?int $priceMinor = null,
    ): void {
        VariantOutletAvailability::query()->create([
            'tenant_id' => $tenant->id,
            'variant_id' => $variant->id,
            'outlet_id' => $outlet->id,
            'available' => $available,
            'price_minor' => $priceMinor,
        ]);
    }

    private function modifierOptionOverride(
        Tenant $tenant,
        Outlet $outlet,
        ModifierOption $option,
        bool $available,
        ?int $priceDeltaMinor = null,
    ): void {
        ModifierOptionOutletOverride::query()->create([
            'tenant_id' => $tenant->id,
            'option_id' => $option->id,
            'outlet_id' => $outlet->id,
            'available' => $available,
            'price_delta_minor' => $priceDeltaMinor,
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
