<?php

declare(strict_types=1);

namespace Tests\Feature\Kitchen;

use App\Modules\Catalog\Domain\Enums\CategoryStatus;
use App\Modules\Catalog\Domain\Enums\ProductStatus;
use App\Modules\Catalog\Domain\Models\Category;
use App\Modules\Catalog\Domain\Models\Product;
use App\Modules\Catalog\Domain\Models\ProductVariant;
use App\Modules\Identity\Domain\Enums\PredefinedRole;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Identity\Domain\Models\UserRoleAssignment;
use App\Modules\Kitchen\Application\Actions\ChangeKitchenTicketStatus;
use App\Modules\Kitchen\Application\Actions\CreateKitchenRoutingRule;
use App\Modules\Kitchen\Application\Actions\CreateKitchenStation;
use App\Modules\Kitchen\Application\Actions\CreateKitchenTicketsForOrder;
use App\Modules\Kitchen\Application\Actions\DispatchKitchenPrintJob;
use App\Modules\Kitchen\Application\Actions\GetKdsSnapshot;
use App\Modules\Kitchen\Application\Actions\ResolveKitchenRouting;
use App\Modules\Kitchen\Application\Contracts\KitchenPrinterDispatcher;
use App\Modules\Kitchen\Application\Data\KitchenRoutingRuleInput;
use App\Modules\Kitchen\Application\Data\KitchenStationInput;
use App\Modules\Kitchen\Application\Data\PrinterDispatchResult;
use App\Modules\Kitchen\Domain\Enums\KitchenRoutingRuleType;
use App\Modules\Kitchen\Domain\Enums\KitchenTicketStatus;
use App\Modules\Kitchen\Domain\Enums\PrintJobStatus;
use App\Modules\Kitchen\Domain\Models\KitchenPrintJob;
use App\Modules\Kitchen\Domain\Models\KitchenTicket;
use App\Modules\Kitchen\Domain\Models\KitchenTicketEvent;
use App\Modules\Kitchen\Domain\Models\KitchenTicketItem;
use App\Modules\Sales\Domain\Enums\OrderStatus;
use App\Modules\Sales\Domain\Enums\ShiftStatus;
use App\Modules\Sales\Domain\Models\Order;
use App\Modules\Sales\Domain\Models\OrderItem;
use App\Modules\Sales\Domain\Models\Shift;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Domain\Enums\MembershipType;
use App\Modules\Tenancy\Domain\Enums\OutletStatus;
use App\Modules\Tenancy\Domain\Enums\TenantStatus;
use App\Modules\Tenancy\Domain\Models\Outlet;
use App\Modules\Tenancy\Domain\Models\Tenant;
use App\Modules\Tenancy\Domain\Models\TenantMembership;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

final class KitchenPhaseSevenTest extends TestCase
{
    use RefreshDatabase;

    public function test_routing_prefers_variant_product_then_category_and_uses_fallback(): void
    {
        $tenant = $this->tenant();
        $owner = $this->user('owner@example.com', $tenant);
        $outlet = $this->outlet($tenant, 'MAIN');
        $context = new TenantRequestContext($tenant->id, $owner->id, MembershipType::Owner);
        [$category, $product, $variant] = $this->catalog($tenant);
        $hot = $this->app->make(CreateKitchenStation::class)->handle($context, new KitchenStationInput($outlet->id, 'Hot Kitchen', 'HOT', false));
        $bar = $this->app->make(CreateKitchenStation::class)->handle($context, new KitchenStationInput($outlet->id, 'Bar', 'BAR', false));
        $expo = $this->app->make(CreateKitchenStation::class)->handle($context, new KitchenStationInput($outlet->id, 'Expo', 'EXPO', true));
        $rules = $this->app->make(CreateKitchenRoutingRule::class);
        $rules->handle($context, new KitchenRoutingRuleInput($outlet->id, $expo->id, KitchenRoutingRuleType::Category, $category->id, 30));
        $rules->handle($context, new KitchenRoutingRuleInput($outlet->id, $hot->id, KitchenRoutingRuleType::Product, $product->id, 20));
        $rules->handle($context, new KitchenRoutingRuleInput($outlet->id, $bar->id, KitchenRoutingRuleType::Variant, $variant->id, 10));

        $routing = $this->app->make(ResolveKitchenRouting::class);

        self::assertSame($bar->id, $routing->handle($context, $outlet->id, $product->id, $variant->id)->stationId);
        self::assertSame($hot->id, $routing->handle($context, $outlet->id, $product->id, null)->stationId);
        self::assertSame($expo->id, $routing->handle($context, $outlet->id, strtolower((string) str()->ulid()), null, $category->id)->stationId);
    }

    public function test_ticket_creation_is_idempotent_and_state_changes_are_evented(): void
    {
        Event::fake();

        $tenant = $this->tenant();
        $owner = $this->user('owner@example.com', $tenant);
        $outlet = $this->outlet($tenant, 'MAIN');
        $context = new TenantRequestContext($tenant->id, $owner->id, MembershipType::Owner);
        [, $product, $variant] = $this->catalog($tenant);
        $station = $this->app->make(CreateKitchenStation::class)->handle($context, new KitchenStationInput($outlet->id, 'Hot Kitchen', 'HOT', true));
        $order = $this->order($tenant, $outlet, $owner);
        $item = $this->orderItem($tenant, $order, $product, $variant);

        $first = $this->app->make(CreateKitchenTicketsForOrder::class)->handle($context, $outlet->id, $order->id);
        $second = $this->app->make(CreateKitchenTicketsForOrder::class)->handle($context, $outlet->id, $order->id);

        self::assertCount(1, $first->tickets);
        self::assertCount(1, $second->tickets);
        self::assertSame(1, KitchenTicket::query()->count());
        self::assertSame(1, KitchenTicketItem::query()->where('order_item_id', $item->id)->count());
        self::assertSame(1, KitchenTicketEvent::query()->where('event_type', 'ticket.created')->count());

        $ticket = KitchenTicket::query()->firstOrFail();
        $ticket = $this->app->make(ChangeKitchenTicketStatus::class)->handle($context, $outlet->id, $ticket->id, KitchenTicketStatus::Preparing);
        $ticket = $this->app->make(ChangeKitchenTicketStatus::class)->handle($context, $outlet->id, $ticket->id, KitchenTicketStatus::Ready);

        self::assertSame(KitchenTicketStatus::Ready, $ticket->status);
        self::assertSame(3, KitchenTicketEvent::query()->count());

        $snapshot = $this->app->make(GetKdsSnapshot::class)->handle($context, $outlet->id, $station->id);

        self::assertCount(1, $snapshot['tickets']);
        self::assertSame('ready', $snapshot['tickets'][0]['status']);
    }

    public function test_printer_dispatch_failure_retry_and_reprint_are_append_only(): void
    {
        $tenant = $this->tenant();
        $owner = $this->user('owner@example.com', $tenant);
        $outlet = $this->outlet($tenant, 'MAIN');
        $context = new TenantRequestContext($tenant->id, $owner->id, MembershipType::Owner);
        [, $product, $variant] = $this->catalog($tenant);
        $this->app->make(CreateKitchenStation::class)->handle($context, new KitchenStationInput($outlet->id, 'Hot Kitchen', 'HOT', true));
        $order = $this->order($tenant, $outlet, $owner);
        $this->orderItem($tenant, $order, $product, $variant);
        $this->app->make(CreateKitchenTicketsForOrder::class)->handle($context, $outlet->id, $order->id);
        $ticket = KitchenTicket::query()->firstOrFail();
        $dispatcher = new class implements KitchenPrinterDispatcher
        {
            public bool $fail = true;

            public function dispatch(KitchenPrintJob $job): PrinterDispatchResult
            {
                return $this->fail
                    ? PrinterDispatchResult::failed('Printer offline')
                    : PrinterDispatchResult::sent();
            }
        };
        $this->app->instance(KitchenPrinterDispatcher::class, $dispatcher);
        $print = $this->app->make(DispatchKitchenPrintJob::class);

        $failed = $print->handle($context, $outlet->id, $ticket->id);

        self::assertSame(PrintJobStatus::Failed, $failed->status);

        $dispatcher->fail = false;
        $retry = $print->retry($context, $outlet->id, $failed->id);
        $reprint = $print->reprint($context, $outlet->id, $ticket->id, 'Guest requested another chit.');

        self::assertSame(PrintJobStatus::Sent, $retry->status);
        self::assertSame($failed->id, $retry->source_print_job_id);
        self::assertSame(PrintJobStatus::Sent, $reprint->status);
        self::assertSame('reprint', $reprint->job_type);
        self::assertSame(3, KitchenPrintJob::query()->count());
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
        UserRoleAssignment::query()->create([
            'user_id' => $user->id,
            'role' => PredefinedRole::TenantOwner,
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

    /**
     * @return array{Category, Product, ProductVariant}
     */
    private function catalog(Tenant $tenant): array
    {
        $category = Category::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Food',
            'display_order' => 1,
            'status' => CategoryStatus::Active,
        ]);
        $product = Product::query()->create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'name' => 'Burger',
            'sku' => 'BRG',
            'base_price_minor' => 50000,
            'currency' => 'IDR',
            'display_order' => 1,
            'status' => ProductStatus::Active,
        ]);
        $variant = ProductVariant::query()->create([
            'tenant_id' => $tenant->id,
            'product_id' => $product->id,
            'name' => 'Large',
            'sku' => 'BRG-L',
            'price_minor' => 60000,
            'currency' => 'IDR',
            'is_default' => false,
            'display_order' => 1,
            'status' => ProductStatus::Active,
        ]);

        return [$category, $product, $variant];
    }

    private function order(Tenant $tenant, Outlet $outlet, User $user): Order
    {
        $shift = Shift::query()->create([
            'tenant_id' => $tenant->id,
            'outlet_id' => $outlet->id,
            'user_id' => $user->id,
            'status' => ShiftStatus::Open,
            'opened_at' => CarbonImmutable::now(),
            'opening_cash_minor' => 0,
            'expected_cash_minor' => 0,
            'gross_sales_minor' => 0,
            'currency' => 'IDR',
        ]);

        return Order::query()->create([
            'tenant_id' => $tenant->id,
            'outlet_id' => $outlet->id,
            'shift_id' => $shift->id,
            'user_id' => $user->id,
            'order_number' => 'ORD-001',
            'status' => OrderStatus::Completed,
            'subtotal_minor' => 60000,
            'discount_minor' => 0,
            'service_charge_minor' => 0,
            'tax_minor' => 0,
            'total_minor' => 60000,
            'currency' => 'IDR',
            'completed_at' => CarbonImmutable::now(),
        ]);
    }

    private function orderItem(Tenant $tenant, Order $order, Product $product, ProductVariant $variant): OrderItem
    {
        return OrderItem::query()->create([
            'tenant_id' => $tenant->id,
            'order_id' => $order->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'product_sku' => $product->sku,
            'variant_sku' => $variant->sku,
            'product_name' => $product->name,
            'variant_name' => $variant->name,
            'product_category_id' => $product->category_id,
            'product_category_name' => 'Food',
            'quantity' => '1.000',
            'unit_price_minor' => 60000,
            'modifier_total_minor' => 0,
            'line_subtotal_minor' => 60000,
            'currency' => 'IDR',
        ]);
    }
}
