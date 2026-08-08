<?php

declare(strict_types=1);

namespace Tests\Feature\Dining;

use App\Modules\Dining\Application\Actions\CloseTableSession;
use App\Modules\Dining\Application\Actions\LinkOrderToTableSession;
use App\Modules\Dining\Application\Actions\MergeTableSession;
use App\Modules\Dining\Application\Actions\OpenTableSession;
use App\Modules\Dining\Application\Actions\TransferTableSession;
use App\Modules\Dining\Application\Data\OpenTableSessionInput;
use App\Modules\Dining\Domain\Enums\TableSessionStatus;
use App\Modules\Dining\Domain\Enums\TableStatus;
use App\Modules\Dining\Domain\Models\DiningFloor;
use App\Modules\Dining\Domain\Models\DiningTable;
use App\Modules\Dining\Domain\Models\DiningTableSessionOrder;
use App\Modules\Identity\Domain\Enums\PredefinedRole;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Identity\Domain\Models\UserRoleAssignment;
use App\Modules\Sales\Domain\Enums\OrderStatus;
use App\Modules\Sales\Domain\Enums\ShiftStatus;
use App\Modules\Sales\Domain\Models\Order;
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

final class TableSessionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_table_session_opens_transfers_links_order_and_closes_after_terminal_order(): void
    {
        $tenant = $this->tenant();
        $owner = $this->user('owner@example.com', $tenant);
        $outlet = $this->outlet($tenant, 'MAIN');
        $floor = $this->floor($tenant, $outlet);
        $tableOne = $this->table($tenant, $outlet, $floor, 'T01');
        $tableTwo = $this->table($tenant, $outlet, $floor, 'T02');
        $context = new TenantRequestContext($tenant->id, $owner->id, MembershipType::Owner);

        $session = $this->app->make(OpenTableSession::class)->handle(
            $context,
            new OpenTableSessionInput($outlet->id, $tableOne->id, 3, 'Birthday'),
        );

        self::assertSame(TableSessionStatus::Open, $session->status);
        self::assertSame($tableOne->id, $session->table_id);

        $this->expectExceptionMessage('The selected dining table already has an open session.');
        $this->app->make(OpenTableSession::class)->handle(
            $context,
            new OpenTableSessionInput($outlet->id, $tableOne->id, 2),
        );
    }

    public function test_transfer_merge_and_close_rules(): void
    {
        $tenant = $this->tenant();
        $owner = $this->user('owner@example.com', $tenant);
        $outlet = $this->outlet($tenant, 'MAIN');
        $floor = $this->floor($tenant, $outlet);
        $tableOne = $this->table($tenant, $outlet, $floor, 'T01');
        $tableTwo = $this->table($tenant, $outlet, $floor, 'T02');
        $tableThree = $this->table($tenant, $outlet, $floor, 'T03');
        $context = new TenantRequestContext($tenant->id, $owner->id, MembershipType::Owner);
        $open = $this->app->make(OpenTableSession::class);
        $session = $open->handle($context, new OpenTableSessionInput($outlet->id, $tableOne->id, 2));
        $source = $open->handle($context, new OpenTableSessionInput($outlet->id, $tableThree->id, 1));

        $transferred = $this->app->make(TransferTableSession::class)->handle($context, $session->id, $tableTwo->id);

        self::assertSame(TableSessionStatus::Open, $transferred->status);
        self::assertSame($tableOne->id, $transferred->previous_table_id);
        self::assertSame($tableTwo->id, $transferred->table_id);

        $order = $this->order($tenant, $outlet, $owner, OrderStatus::Draft);
        $this->app->make(LinkOrderToTableSession::class)->handle($context, $source->id, $order->id);

        $merged = $this->app->make(MergeTableSession::class)->handle($context, $source->id, $transferred->id);

        self::assertSame(TableSessionStatus::Merged, $merged->status);
        self::assertSame($transferred->id, $merged->target_session_id);
        self::assertSame(
            $transferred->id,
            DiningTableSessionOrder::query()->where('order_id', $order->id)->firstOrFail()->table_session_id,
        );

        $this->expectExceptionMessage('The dining table session is not in a valid state for this action.');
        $this->app->make(CloseTableSession::class)->handle($context, $transferred->id);
    }

    public function test_close_requires_all_linked_orders_terminal(): void
    {
        $tenant = $this->tenant();
        $owner = $this->user('owner@example.com', $tenant);
        $outlet = $this->outlet($tenant, 'MAIN');
        $floor = $this->floor($tenant, $outlet);
        $table = $this->table($tenant, $outlet, $floor, 'T01');
        $context = new TenantRequestContext($tenant->id, $owner->id, MembershipType::Owner);
        $session = $this->app->make(OpenTableSession::class)->handle($context, new OpenTableSessionInput($outlet->id, $table->id, 2));
        $order = $this->order($tenant, $outlet, $owner, OrderStatus::Completed);
        $this->app->make(LinkOrderToTableSession::class)->handle($context, $session->id, $order->id);

        $closed = $this->app->make(CloseTableSession::class)->handle($context, $session->id);

        self::assertSame(TableSessionStatus::Closed, $closed->status);
        self::assertNull($closed->open_table_key);
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

    private function table(Tenant $tenant, Outlet $outlet, DiningFloor $floor, string $code): DiningTable
    {
        return DiningTable::query()->create([
            'tenant_id' => $tenant->id,
            'outlet_id' => $outlet->id,
            'floor_id' => $floor->id,
            'name' => "{$code} Table",
            'code' => $code,
            'capacity' => 2,
            'display_order' => 1,
            'status' => TableStatus::Active,
        ]);
    }

    private function order(Tenant $tenant, Outlet $outlet, User $user, OrderStatus $status): Order
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
            'status' => $status,
            'subtotal_minor' => 0,
            'discount_minor' => 0,
            'service_charge_minor' => 0,
            'tax_minor' => 0,
            'total_minor' => 0,
            'currency' => 'IDR',
        ]);
    }
}
