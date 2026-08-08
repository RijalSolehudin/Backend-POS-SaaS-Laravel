<?php

declare(strict_types=1);

namespace Tests\Feature\Growth;

use App\Modules\Catalog\Domain\Enums\CategoryStatus;
use App\Modules\Catalog\Domain\Enums\ProductStatus;
use App\Modules\Catalog\Domain\Models\Category;
use App\Modules\Catalog\Domain\Models\Product;
use App\Modules\Catalog\Domain\Models\ProductOutletAvailability;
use App\Modules\Dining\Application\Actions\OpenTableSession;
use App\Modules\Dining\Application\Data\OpenTableSessionInput;
use App\Modules\Dining\Domain\Enums\TableStatus;
use App\Modules\Dining\Domain\Models\DiningFloor;
use App\Modules\Dining\Domain\Models\DiningTable;
use App\Modules\Identity\Domain\Enums\PredefinedRole;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Identity\Domain\Models\UserRoleAssignment;
use App\Modules\OrderingChannel\Application\Actions\AddCustomerCartItem;
use App\Modules\OrderingChannel\Application\Actions\ConfirmOrderRequest;
use App\Modules\OrderingChannel\Application\Actions\CreateCustomerCart;
use App\Modules\OrderingChannel\Application\Actions\CreateQrSession;
use App\Modules\OrderingChannel\Application\Actions\CreateWaiterOrder;
use App\Modules\OrderingChannel\Application\Actions\PublicQrCatalog;
use App\Modules\OrderingChannel\Application\Actions\SubmitOrderRequest;
use App\Modules\OrderingChannel\Application\Data\CustomerCartItemInput;
use App\Modules\OrderingChannel\Domain\Enums\OrderRequestStatus;
use App\Modules\OrderingChannel\Domain\Models\OrderingOrderRequest;
use App\Modules\PaymentsGateway\Application\Actions\CreatePaymentIntent;
use App\Modules\PaymentsGateway\Application\Actions\HandlePaymentWebhook;
use App\Modules\PaymentsGateway\Domain\Enums\PaymentIntentStatus;
use App\Modules\PaymentsGateway\Domain\Models\PaymentGatewayIntent;
use App\Modules\PaymentsGateway\Domain\Models\PaymentGatewayWebhookEvent;
use App\Modules\Promotion\Application\Actions\ApplyPromotionToOrder;
use App\Modules\Promotion\Application\Actions\CreatePromotionRule;
use App\Modules\Promotion\Application\Data\PromotionRuleInput;
use App\Modules\Promotion\Domain\Enums\PromotionDiscountType;
use App\Modules\Promotion\Domain\Models\SalesOrderDiscount;
use App\Modules\Reporting\Application\Actions\CreateAnalyticsExport;
use App\Modules\Reservation\Application\Actions\CreateReservation;
use App\Modules\Reservation\Application\Actions\SeatReservation;
use App\Modules\Reservation\Application\Data\ReservationInput;
use App\Modules\Reservation\Domain\Enums\ReservationStatus;
use App\Modules\Sales\Application\Data\OrderItemSelection;
use App\Modules\Sales\Domain\Enums\OrderStatus;
use App\Modules\Sales\Domain\Enums\ShiftStatus;
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
use Tests\TestCase;

final class GrowthPhaseEightTest extends TestCase
{
    use RefreshDatabase;

    public function test_qr_cart_staff_confirmation_and_waiter_order_use_sales_boundary(): void
    {
        [$tenant, $outlet, $owner, $product] = $this->foundation();
        $context = new TenantRequestContext($tenant->id, $owner->id, MembershipType::Owner);
        $this->openShift($tenant, $outlet, $owner);
        $qr = $this->app->make(CreateQrSession::class)->handle($context, $outlet->id, null, 'pickup');
        $catalog = $this->app->make(PublicQrCatalog::class)->handle($qr['token']);

        self::assertSame($outlet->id, $catalog['session']['outlet_id']);
        self::assertCount(1, $catalog['catalog']);

        $cart = $this->app->make(CreateCustomerCart::class)->handle($qr['token'], 'Rina', '081234567');
        $this->app->make(AddCustomerCartItem::class)->handle($cart, new CustomerCartItemInput($product->id, '2.000'));
        $request = $this->app->make(SubmitOrderRequest::class)->handle($cart);
        $order = $this->app->make(ConfirmOrderRequest::class)->handle($context, $request->id, 'confirm-1');
        $retry = $this->app->make(ConfirmOrderRequest::class)->handle($context, $request->id, 'confirm-1');

        self::assertSame($order->id, $retry->id);
        self::assertSame(100000, $order->total_minor);
        self::assertSame(OrderRequestStatus::Confirmed, OrderingOrderRequest::query()->firstOrFail()->status);

        $waiterOrder = $this->app->make(CreateWaiterOrder::class)->handle(
            $context,
            $outlet->id,
            [new OrderItemSelection($product->id, '1.000')],
            null,
            'waiter-1',
        );

        self::assertSame(50000, $waiterOrder->total_minor);
    }

    public function test_payment_webhook_is_signed_idempotent_and_completes_order_after_paid(): void
    {
        [$tenant, $outlet, $owner, $product] = $this->foundation();
        $context = new TenantRequestContext($tenant->id, $owner->id, MembershipType::Owner);
        $this->openShift($tenant, $outlet, $owner);
        $order = $this->app->make(CreateWaiterOrder::class)->handle(
            $context,
            $outlet->id,
            [new OrderItemSelection($product->id, '1.000')],
            null,
            'gateway-order-1',
        );
        $intent = $this->app->make(CreatePaymentIntent::class)->handle($context, $outlet->id, $order->id);
        $payload = json_encode([
            'event_id' => 'evt_1',
            'type' => 'payment_intent.paid',
            'intent_id' => $intent->provider_intent_id,
            'card_number' => '4111111111111111',
        ], JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $payload, 'local-secret');

        $event = $this->app->make(HandlePaymentWebhook::class)->handle($payload, $signature);
        $replay = $this->app->make(HandlePaymentWebhook::class)->handle($payload, $signature);

        self::assertSame($event->id, $replay->id);
        self::assertSame(PaymentIntentStatus::Paid, PaymentGatewayIntent::query()->firstOrFail()->status);
        self::assertSame(OrderStatus::Completed, $order->refresh()->status);
        self::assertArrayNotHasKey('card_number', PaymentGatewayWebhookEvent::query()->firstOrFail()->payload ?? []);
    }

    public function test_reservation_promotion_and_analytics_export(): void
    {
        [$tenant, $outlet, $owner, $product] = $this->foundation();
        $context = new TenantRequestContext($tenant->id, $owner->id, MembershipType::Owner);
        $this->openShift($tenant, $outlet, $owner);
        $floor = $this->floor($tenant, $outlet);
        $table = $this->table($tenant, $outlet, $floor);
        $session = $this->app->make(OpenTableSession::class)->handle($context, new OpenTableSessionInput($outlet->id, $table->id, 2));
        $reservation = $this->app->make(CreateReservation::class)->handle(
            $context,
            new ReservationInput($outlet->id, CarbonImmutable::now()->addHour(), 2, customerPhone: '081234567'),
        );

        $seated = $this->app->make(SeatReservation::class)->handle($context, $reservation->id, $session->id);

        self::assertSame(ReservationStatus::Seated, $seated->status);
        self::assertSame($session->id, $seated->table_session_id);

        $order = $this->app->make(CreateWaiterOrder::class)->handle(
            $context,
            $outlet->id,
            [new OrderItemSelection($product->id, '2.000')],
            $session->id,
            'promo-order-1',
        );
        $promotion = $this->app->make(CreatePromotionRule::class)->handle(
            $context,
            new PromotionRuleInput('Ten Percent', 'TEN10', PromotionDiscountType::Percentage, 1000),
        );
        $discount = $this->app->make(ApplyPromotionToOrder::class)->handle($context, $outlet->id, $order->id, $promotion->id);

        self::assertSame(10000, $discount->discount_amount_minor);
        self::assertSame(90000, $order->refresh()->total_minor);
        self::assertSame(1, SalesOrderDiscount::query()->count());

        $export = $this->app->make(CreateAnalyticsExport::class)->handle(
            $context,
            'growth_summary',
            ['customer_phone' => '081234567', 'card_number' => '4111111111111111'],
            $outlet->id,
        );

        self::assertSame('[redacted]', $export->filters['customer_phone'] ?? null);
        self::assertArrayNotHasKey('card_number', $export->filters ?? []);
        self::assertSame(1, $export->result['reservations_count'] ?? null);
    }

    /**
     * @return array{Tenant, Outlet, User, Product}
     */
    private function foundation(): array
    {
        $tenant = Tenant::query()->create([
            'name' => 'Acme POS',
            'code' => 'acme-pos',
            'status' => TenantStatus::Active,
            'currency' => 'IDR',
            'timezone' => 'Asia/Jakarta',
        ]);
        $outlet = Outlet::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Main Outlet',
            'code' => 'MAIN',
            'status' => OutletStatus::Active,
        ]);
        $owner = User::factory()->create(['email' => 'owner@example.com']);
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'membership_type' => MembershipType::Owner,
        ]);
        UserRoleAssignment::query()->create([
            'user_id' => $owner->id,
            'role' => PredefinedRole::TenantOwner,
        ]);
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
        ProductOutletAvailability::query()->create([
            'tenant_id' => $tenant->id,
            'product_id' => $product->id,
            'outlet_id' => $outlet->id,
            'available' => true,
        ]);

        return [$tenant, $outlet, $owner, $product];
    }

    private function openShift(Tenant $tenant, Outlet $outlet, User $user): void
    {
        Shift::query()->create([
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
    }

    private function floor(Tenant $tenant, Outlet $outlet): DiningFloor
    {
        return DiningFloor::query()->create([
            'tenant_id' => $tenant->id,
            'outlet_id' => $outlet->id,
            'name' => 'Main Floor',
            'code' => 'MAIN',
            'display_order' => 1,
            'status' => TableStatus::Active,
        ]);
    }

    private function table(Tenant $tenant, Outlet $outlet, DiningFloor $floor): DiningTable
    {
        return DiningTable::query()->create([
            'tenant_id' => $tenant->id,
            'outlet_id' => $outlet->id,
            'floor_id' => $floor->id,
            'name' => 'Table 1',
            'code' => 'T01',
            'capacity' => 2,
            'display_order' => 1,
            'status' => TableStatus::Active,
        ]);
    }
}
