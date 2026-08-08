<?php

declare(strict_types=1);

namespace Tests\Feature\Sync;

use App\Modules\Catalog\Domain\Enums\CategoryStatus;
use App\Modules\Catalog\Domain\Enums\ProductStatus;
use App\Modules\Catalog\Domain\Models\Category;
use App\Modules\Catalog\Domain\Models\Product;
use App\Modules\Catalog\Domain\Models\ProductOutletAvailability;
use App\Modules\Identity\Domain\Enums\PredefinedRole;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Identity\Domain\Models\UserRoleAssignment;
use App\Modules\Sales\Domain\Enums\OrderStatus;
use App\Modules\Sales\Domain\Enums\ShiftStatus;
use App\Modules\Sales\Domain\Models\Order;
use App\Modules\Sales\Domain\Models\Receipt;
use App\Modules\Sales\Domain\Models\Shift;
use App\Modules\Sync\Application\Actions\CheckRecoveryObjectives;
use App\Modules\Sync\Application\Actions\GetOfflineCatalogSnapshot;
use App\Modules\Sync\Application\Actions\GetSyncBootstrapPolicy;
use App\Modules\Sync\Application\Actions\ProcessSyncMutation;
use App\Modules\Sync\Application\Actions\PullSyncOutbox;
use App\Modules\Sync\Application\Actions\RecordPerformanceBaseline;
use App\Modules\Sync\Application\Actions\ResolveSyncConflict;
use App\Modules\Sync\Application\Data\SyncMutationInput;
use App\Modules\Sync\Application\Exceptions\SyncException;
use App\Modules\Sync\Domain\Enums\OfflineOrderStatus;
use App\Modules\Sync\Domain\Enums\PerformanceBaselineStatus;
use App\Modules\Sync\Domain\Enums\SyncConflictStatus;
use App\Modules\Sync\Domain\Enums\SyncRecordStatus;
use App\Modules\Sync\Domain\Models\OfflineOrderDraft;
use App\Modules\Sync\Domain\Models\PerformanceBaseline;
use App\Modules\Sync\Domain\Models\SyncConflict;
use App\Modules\Sync\Domain\Models\SyncDeviceState;
use App\Modules\Sync\Domain\Models\SyncInboxRecord;
use App\Modules\Sync\Domain\Models\SyncOutboxRecord;
use App\Modules\Tenancy\Application\Data\PosOutletContext;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Domain\Enums\MembershipType;
use App\Modules\Tenancy\Domain\Enums\OutletStatus;
use App\Modules\Tenancy\Domain\Enums\PosDeviceStatus;
use App\Modules\Tenancy\Domain\Enums\TenantStatus;
use App\Modules\Tenancy\Domain\Models\Outlet;
use App\Modules\Tenancy\Domain\Models\PosDevice;
use App\Modules\Tenancy\Domain\Models\Tenant;
use App\Modules\Tenancy\Domain\Models\TenantMembership;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SyncPhaseNineTest extends TestCase
{
    use RefreshDatabase;

    public function test_bootstrap_and_catalog_snapshot_publish_offline_policy_without_server_side_keys(): void
    {
        [$tenant, $outlet, $owner, $device] = $this->foundation();
        $product = $this->catalogProduct($tenant, $outlet);
        $policy = $this->app->make(GetSyncBootstrapPolicy::class)->handle($tenant->id, $outlet->id, $device->id);
        $snapshot = $this->app->make(GetOfflineCatalogSnapshot::class)->handle($this->posContext($tenant, $outlet, $owner, $device));

        self::assertFalse($policy['device_revoked']);
        self::assertTrue($policy['requires_local_encryption']);
        self::assertFalse($policy['server_accepts_local_encryption_keys']);
        self::assertSame($product->id, $snapshot['catalog'][0]->id);
        self::assertSame(1, SyncOutboxRecord::query()->where('event_type', 'catalog.snapshot.generated')->count());
    }

    public function test_push_mutations_are_idempotent_and_payload_conflicts_require_review(): void
    {
        [$tenant, $outlet, $owner, $device] = $this->foundation();
        $context = $this->posContext($tenant, $outlet, $owner, $device);
        $payload = ['client_order_id' => 'local-order-1'];
        $input = $this->syncInput($context, 'local-order-1', 'offline_order.create_draft', 1, $payload);
        $process = $this->app->make(ProcessSyncMutation::class);

        $accepted = $process->handle($context, $input);
        $duplicate = $process->handle($context, $input);
        $conflict = $process->handle($context, new SyncMutationInput(
            tenantId: $context->tenantId,
            outletId: $context->outletId,
            deviceId: $context->deviceId,
            clientRecordId: 'local-order-1',
            action: 'offline_order.create_draft',
            sequenceNumber: 1,
            idempotencyKey: 'sync-1',
            payloadHash: hash('sha256', 'changed'),
            payload: ['client_order_id' => 'local-order-1', 'note' => 'changed'],
        ));

        self::assertSame(SyncRecordStatus::Accepted->value, $accepted->status);
        self::assertSame(SyncRecordStatus::Duplicate->value, $duplicate->status);
        self::assertSame(SyncRecordStatus::Conflict->value, $conflict->status);
        self::assertSame(1, SyncInboxRecord::query()->count());
        self::assertSame(1, SyncConflict::query()->where('conflict_type', 'payload_hash_conflict')->count());
    }

    public function test_revoked_devices_cannot_sync_new_mutations(): void
    {
        [$tenant, $outlet, $owner, $device] = $this->foundation(deviceStatus: PosDeviceStatus::Revoked);
        $context = $this->posContext($tenant, $outlet, $owner, $device);

        $this->expectException(SyncException::class);
        $this->expectExceptionMessage('The POS device is revoked and cannot sync new mutations.');

        $this->app->make(ProcessSyncMutation::class)->handle(
            $context,
            $this->syncInput($context, 'local-order-1', 'offline_order.create_draft', 1, ['client_order_id' => 'local-order-1']),
        );
    }

    public function test_offline_order_completion_replays_through_sales_and_returns_authoritative_order(): void
    {
        [$tenant, $outlet, $owner, $device] = $this->foundation();
        $product = $this->catalogProduct($tenant, $outlet);
        $this->openShift($tenant, $outlet, $owner);
        $context = $this->posContext($tenant, $outlet, $owner, $device);
        $process = $this->app->make(ProcessSyncMutation::class);
        $process->handle($context, $this->syncInput($context, 'local-order-2', 'offline_order.create_draft', 1, ['client_order_id' => 'local-order-2']));
        $completePayload = [
            'client_order_id' => 'local-order-2',
            'items' => [
                ['product_id' => $product->id, 'quantity' => '1.000'],
            ],
            'amount_minor' => 50000,
            'currency' => 'IDR',
        ];

        $result = $process->handle($context, $this->syncInput($context, 'local-order-2-complete', 'offline_order.complete_cash', 2, $completePayload));

        self::assertSame(SyncRecordStatus::Accepted->value, $result->status);
        self::assertSame(OrderStatus::Completed, Order::query()->firstOrFail()->status);
        self::assertSame(1, Receipt::query()->count());
        self::assertSame(OfflineOrderStatus::Accepted, OfflineOrderDraft::query()->firstOrFail()->status);
        self::assertSame(1, SyncOutboxRecord::query()->where('event_type', 'offline_order.accepted')->count());
    }

    public function test_stale_catalog_or_stock_conflict_does_not_create_financial_order(): void
    {
        [$tenant, $outlet, $owner, $device] = $this->foundation();
        $product = $this->catalogProduct($tenant, $outlet);
        $this->openShift($tenant, $outlet, $owner);
        $context = $this->posContext($tenant, $outlet, $owner, $device);
        $process = $this->app->make(ProcessSyncMutation::class);
        $process->handle($context, $this->syncInput($context, 'local-order-3', 'offline_order.create_draft', 1, ['client_order_id' => 'local-order-3']));
        $payload = [
            'client_order_id' => 'local-order-3',
            'items' => [
                ['product_id' => $product->id, 'quantity' => '1.000'],
            ],
            'amount_minor' => 50000,
            'currency' => 'IDR',
            'insufficient_stock' => true,
        ];

        $result = $process->handle($context, $this->syncInput($context, 'local-order-3-complete', 'offline_order.complete_cash', 2, $payload));

        self::assertSame(SyncRecordStatus::Conflict->value, $result->status);
        self::assertSame(0, Order::query()->count());
        self::assertSame(OfflineOrderStatus::Conflict, OfflineOrderDraft::query()->firstOrFail()->status);
        self::assertSame(1, SyncConflict::query()->where('conflict_type', 'stale_catalog_or_stock')->count());
    }

    public function test_pull_outbox_advances_device_cursor(): void
    {
        [$tenant, $outlet, $owner, $device] = $this->foundation();
        $context = $this->posContext($tenant, $outlet, $owner, $device);
        SyncOutboxRecord::query()->create([
            'tenant_id' => $tenant->id,
            'outlet_id' => $outlet->id,
            'event_type' => 'sync.test',
            'resource_type' => 'test',
            'resource_id' => null,
            'payload' => ['ok' => true],
        ]);

        $records = $this->app->make(PullSyncOutbox::class)->handle($context);

        self::assertCount(1, $records);
        self::assertSame($records[0]->id, SyncDeviceState::query()->firstOrFail()->last_outbox_cursor);
    }

    public function test_conflicts_can_be_resolved_by_authorized_tenant_operator(): void
    {
        [$tenant, $outlet, $owner, $device] = $this->foundation();
        $conflict = SyncConflict::query()->create([
            'tenant_id' => $tenant->id,
            'outlet_id' => $outlet->id,
            'device_id' => $device->id,
            'conflict_type' => 'payload_hash_conflict',
            'status' => SyncConflictStatus::Open,
            'payload' => ['client_record_id' => 'local-order-1'],
        ]);

        $resolved = $this->app->make(ResolveSyncConflict::class)->handle(
            new TenantRequestContext($tenant->id, $owner->id, MembershipType::Owner),
            $conflict->id,
            'Audited against receipt journal.',
        );

        self::assertSame(SyncConflictStatus::Resolved, $resolved->status);
        self::assertSame($owner->id, $resolved->resolved_by);
    }

    public function test_performance_and_recovery_baselines_are_recorded_and_enforced(): void
    {
        $baseline = $this->app->make(RecordPerformanceBaseline::class)->handle('sync_push_p95', 1000, 900);
        $objectives = $this->app->make(CheckRecoveryObjectives::class)->handle(300, 240, 1800, 1200);

        self::assertSame(PerformanceBaselineStatus::Passed, $baseline->status);
        self::assertTrue($objectives['rpo_passed']);
        self::assertTrue($objectives['rto_passed']);
        self::assertSame(3, PerformanceBaseline::query()->count());
    }

    /**
     * @return array{Tenant, Outlet, User, PosDevice}
     */
    private function foundation(PosDeviceStatus $deviceStatus = PosDeviceStatus::Active): array
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
        $owner = User::factory()->create(['email' => strtolower((string) str()->ulid()).'@example.com']);
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'membership_type' => MembershipType::Owner,
        ]);
        UserRoleAssignment::query()->create([
            'user_id' => $owner->id,
            'role' => PredefinedRole::TenantOwner,
        ]);
        $device = PosDevice::query()->create([
            'installation_id' => strtolower((string) str()->ulid()),
            'tenant_id' => $tenant->id,
            'outlet_id' => $outlet->id,
            'name' => 'Register 1',
            'client_type' => 'pos_terminal',
            'platform' => 'flutter',
            'app_version' => '1.0.0',
            'status' => $deviceStatus,
            'registered_by' => $owner->id,
            'last_seen_at' => CarbonImmutable::now(),
            'revoked_at' => $deviceStatus === PosDeviceStatus::Revoked ? CarbonImmutable::now() : null,
            'revoked_by' => $deviceStatus === PosDeviceStatus::Revoked ? $owner->id : null,
            'revoked_reason' => $deviceStatus === PosDeviceStatus::Revoked ? 'Test revocation' : null,
        ]);

        return [$tenant, $outlet, $owner, $device];
    }

    private function catalogProduct(Tenant $tenant, Outlet $outlet): Product
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
            'sku' => strtolower((string) str()->ulid()),
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

        return $product;
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

    private function posContext(Tenant $tenant, Outlet $outlet, User $user, PosDevice $device): PosOutletContext
    {
        return new PosOutletContext($tenant->id, $outlet->id, $device->id, $user->id);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function syncInput(
        PosOutletContext $context,
        string $clientRecordId,
        string $action,
        int $sequenceNumber,
        array $payload,
    ): SyncMutationInput {
        return new SyncMutationInput(
            tenantId: $context->tenantId,
            outletId: $context->outletId,
            deviceId: $context->deviceId,
            clientRecordId: $clientRecordId,
            action: $action,
            sequenceNumber: $sequenceNumber,
            idempotencyKey: 'sync-'.$sequenceNumber.'-'.$clientRecordId,
            payloadHash: hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)),
            payload: $payload,
        );
    }
}
