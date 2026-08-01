<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Modules\Identity\Domain\Enums\PredefinedRole;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Identity\Domain\Models\UserRoleAssignment;
use App\Modules\Inventory\Domain\Enums\InventoryStatus;
use App\Modules\Inventory\Domain\Enums\StockMovementType;
use App\Modules\Inventory\Domain\Models\InventoryBalance;
use App\Modules\Inventory\Domain\Models\InventoryItem;
use App\Modules\Inventory\Domain\Models\InventoryStockMovement;
use App\Modules\Inventory\Domain\Models\InventoryUnit;
use App\Modules\Procurement\Application\Actions\ApprovePurchaseOrder;
use App\Modules\Procurement\Application\Actions\CreatePurchaseOrder;
use App\Modules\Procurement\Application\Actions\CreateSupplier;
use App\Modules\Procurement\Application\Actions\MarkPurchaseOrderOrdered;
use App\Modules\Procurement\Application\Actions\RecordGoodsReceipt;
use App\Modules\Procurement\Application\Actions\RecordPurchaseReturn;
use App\Modules\Procurement\Application\Actions\SubmitPurchaseOrder;
use App\Modules\Procurement\Application\Actions\UpsertSupplierItem;
use App\Modules\Procurement\Application\Data\GoodsReceiptInput;
use App\Modules\Procurement\Application\Data\GoodsReceiptLineInput;
use App\Modules\Procurement\Application\Data\PurchaseOrderInput;
use App\Modules\Procurement\Application\Data\PurchaseOrderLineInput;
use App\Modules\Procurement\Application\Data\PurchaseReturnInput;
use App\Modules\Procurement\Application\Data\PurchaseReturnLineInput;
use App\Modules\Procurement\Application\Data\SupplierInput;
use App\Modules\Procurement\Application\Data\SupplierItemInput;
use App\Modules\Procurement\Application\Exceptions\ProcurementException;
use App\Modules\Procurement\Domain\Enums\PurchaseOrderStatus;
use App\Modules\Procurement\Domain\Enums\SupplierStatus;
use App\Modules\Procurement\Domain\Models\GoodsReceiptLine;
use App\Modules\Procurement\Domain\Models\PurchaseOrder;
use App\Modules\Procurement\Domain\Models\PurchaseOrderLine;
use App\Modules\Procurement\Domain\Models\PurchaseReturnLine;
use App\Modules\Procurement\Domain\Models\Supplier;
use App\Modules\Procurement\Domain\Models\SupplierItem;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Domain\Enums\MembershipType;
use App\Modules\Tenancy\Domain\Enums\OutletStatus;
use App\Modules\Tenancy\Domain\Enums\TenantStatus;
use App\Modules\Tenancy\Domain\Models\Outlet;
use App\Modules\Tenancy\Domain\Models\Tenant;
use App\Modules\Tenancy\Domain\Models\TenantMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProcurementPhaseSixTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_manages_supplier_header_from_web(): void
    {
        $tenant = $this->tenant();
        $owner = $this->user('owner@example.com', $tenant);
        $this->login($owner);

        $this->get(route('tenant.procurement.index', ['tenant' => $tenant->id]))
            ->assertOk()
            ->assertSee('Procurement');

        $this->post(route('tenant.procurement.suppliers.store', ['tenant' => $tenant->id]), [
            'name' => 'Fresh Farm',
            'code' => 'fresh',
        ])->assertRedirect();

        $supplier = Supplier::query()->where('tenant_id', $tenant->id)->firstOrFail();

        self::assertSame('FRESH', $supplier->code);
        self::assertSame(SupplierStatus::Active, $supplier->status);
    }

    public function test_supplier_item_mapping_rejects_cross_tenant_inventory_item(): void
    {
        $tenant = $this->tenant('tenant-one');
        $otherTenant = $this->tenant('tenant-two');
        $owner = $this->user('owner@example.com', $tenant);
        $context = new TenantRequestContext($tenant->id, $owner->id, MembershipType::Owner);
        $supplier = $this->app->make(CreateSupplier::class)->handle($context, new SupplierInput('Fresh Farm', 'FRESH'));
        $otherItem = $this->inventoryItem($otherTenant, $this->unit($otherTenant), 'MILK');

        $this->expectException(ProcurementException::class);
        $this->expectExceptionMessage('The selected procurement reference belongs to another tenant.');

        $this->app->make(UpsertSupplierItem::class)->handle($context, new SupplierItemInput(
            supplierId: $supplier->id,
            inventoryItemId: $otherItem->id,
            supplierSku: 'MILK-1L',
            lastPriceMinor: 10000,
            currency: 'IDR',
        ));
    }

    public function test_purchase_order_lifecycle_is_approved_before_ordered_and_idempotent(): void
    {
        [$context, $outlet, $supplierItem] = $this->procurementSetup();
        $po = $this->purchaseOrder($context, $outlet, $supplierItem, '5.000');

        $submitted = $this->app->make(SubmitPurchaseOrder::class)->handle($context, $po->id, 'submit-po-1');
        $approved = $this->app->make(ApprovePurchaseOrder::class)->handle($context, $po->id, 'approve-po-1');
        $retry = $this->app->make(ApprovePurchaseOrder::class)->handle($context, $po->id, 'approve-po-1');
        $ordered = $this->app->make(MarkPurchaseOrderOrdered::class)->handle($context, $po->id);

        self::assertSame(PurchaseOrderStatus::Submitted, $submitted->status);
        self::assertSame(PurchaseOrderStatus::Approved, $approved->status);
        self::assertSame($approved->id, $retry->id);
        self::assertSame(PurchaseOrderStatus::Ordered, $ordered->status);
    }

    public function test_goods_receipt_records_partial_and_full_inventory_movements(): void
    {
        [$context, $outlet, $supplierItem] = $this->procurementSetup();
        $po = $this->purchaseOrder($context, $outlet, $supplierItem, '5.000');
        $line = PurchaseOrderLine::query()->where('purchase_order_id', $po->id)->firstOrFail();
        $this->app->make(SubmitPurchaseOrder::class)->handle($context, $po->id, 'submit-receipt-po');
        $this->app->make(ApprovePurchaseOrder::class)->handle($context, $po->id, 'approve-receipt-po');
        $this->app->make(MarkPurchaseOrderOrdered::class)->handle($context, $po->id);

        $partial = $this->app->make(RecordGoodsReceipt::class)->handle($context, new GoodsReceiptInput($po->id, [
            new GoodsReceiptLineInput($line->id, '2.000'),
        ]), 'receipt-partial');
        $full = $this->app->make(RecordGoodsReceipt::class)->handle($context, new GoodsReceiptInput($po->id, [
            new GoodsReceiptLineInput($line->id, '3.000'),
        ]), 'receipt-full');

        self::assertSame(PurchaseOrderStatus::Received, $po->refresh()->status);
        self::assertSame($partial->purchase_order_id, $full->purchase_order_id);
        self::assertSame(2, InventoryStockMovement::query()->where('movement_type', StockMovementType::PurchaseReceipt)->count());
        self::assertSame('5.000', InventoryBalance::query()->where('item_id', $supplierItem->inventory_item_id)->firstOrFail()->quantity);
        self::assertSame(50000, InventoryBalance::query()->where('item_id', $supplierItem->inventory_item_id)->firstOrFail()->total_cost_minor);
    }

    public function test_goods_receipt_rejects_over_receipt(): void
    {
        [$context, $outlet, $supplierItem] = $this->procurementSetup();
        $po = $this->purchaseOrder($context, $outlet, $supplierItem, '1.000');
        $line = PurchaseOrderLine::query()->where('purchase_order_id', $po->id)->firstOrFail();
        $this->app->make(SubmitPurchaseOrder::class)->handle($context, $po->id, 'submit-over-po');
        $this->app->make(ApprovePurchaseOrder::class)->handle($context, $po->id, 'approve-over-po');

        $this->expectException(ProcurementException::class);
        $this->expectExceptionMessage('Goods receipt quantity cannot exceed the remaining purchase order quantity.');

        $this->app->make(RecordGoodsReceipt::class)->handle($context, new GoodsReceiptInput($po->id, [
            new GoodsReceiptLineInput($line->id, '2.000'),
        ]), 'receipt-over');
    }

    public function test_purchase_return_records_outbound_inventory_and_rejects_excess_return(): void
    {
        [$context, $outlet, $supplierItem] = $this->procurementSetup();
        $po = $this->purchaseOrder($context, $outlet, $supplierItem, '4.000');
        $line = PurchaseOrderLine::query()->where('purchase_order_id', $po->id)->firstOrFail();
        $this->app->make(SubmitPurchaseOrder::class)->handle($context, $po->id, 'submit-return-po');
        $this->app->make(ApprovePurchaseOrder::class)->handle($context, $po->id, 'approve-return-po');
        $receipt = $this->app->make(RecordGoodsReceipt::class)->handle($context, new GoodsReceiptInput($po->id, [
            new GoodsReceiptLineInput($line->id, '4.000'),
        ]), 'receipt-return');
        $receiptLine = GoodsReceiptLine::query()->where('goods_receipt_id', $receipt->id)->firstOrFail();

        $return = $this->app->make(RecordPurchaseReturn::class)->handle($context, new PurchaseReturnInput($receipt->id, 'Damaged packaging', [
            new PurchaseReturnLineInput($receiptLine->id, '1.500'),
        ]), 'return-damaged');

        self::assertSame(1, PurchaseReturnLine::query()->where('purchase_return_id', $return->id)->count());
        self::assertSame(1, InventoryStockMovement::query()->where('movement_type', StockMovementType::PurchaseReturn)->count());
        self::assertSame('2.500', InventoryBalance::query()->where('item_id', $supplierItem->inventory_item_id)->firstOrFail()->quantity);

        $this->expectException(ProcurementException::class);
        $this->expectExceptionMessage('Purchase return quantity cannot exceed received quantity remaining.');

        $this->app->make(RecordPurchaseReturn::class)->handle($context, new PurchaseReturnInput($receipt->id, 'Second return', [
            new PurchaseReturnLineInput($receiptLine->id, '3.000'),
        ]), 'return-excess');
    }

    /**
     * @return array{TenantRequestContext, Outlet, SupplierItem}
     */
    private function procurementSetup(): array
    {
        $tenant = $this->tenant();
        $owner = $this->user('owner@example.com', $tenant);
        $outlet = $this->outlet($tenant);
        $unit = $this->unit($tenant);
        $item = $this->inventoryItem($tenant, $unit, 'MILK');
        $context = new TenantRequestContext($tenant->id, $owner->id, MembershipType::Owner);
        $supplier = $this->app->make(CreateSupplier::class)->handle($context, new SupplierInput('Fresh Farm', 'FRESH'));
        $supplierItem = $this->app->make(UpsertSupplierItem::class)->handle($context, new SupplierItemInput(
            supplierId: $supplier->id,
            inventoryItemId: $item->id,
            supplierSku: 'MILK-1L',
            lastPriceMinor: 10000,
            currency: 'IDR',
        ));

        return [$context, $outlet, $supplierItem];
    }

    private function purchaseOrder(TenantRequestContext $context, Outlet $outlet, SupplierItem $supplierItem, string $quantity): PurchaseOrder
    {
        return $this->app->make(CreatePurchaseOrder::class)->handle($context, new PurchaseOrderInput(
            outletId: $outlet->id,
            supplierId: $supplierItem->supplier_id,
            currency: 'IDR',
            lines: [new PurchaseOrderLineInput($supplierItem->id, $quantity, 10000)],
        ));
    }

    private function tenant(string $code = 'tenant-one'): Tenant
    {
        return Tenant::query()->create([
            'name' => str($code)->headline()->toString(),
            'code' => $code,
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

    private function outlet(Tenant $tenant): Outlet
    {
        return Outlet::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Main Outlet',
            'code' => 'MAIN',
            'status' => OutletStatus::Active,
        ]);
    }

    private function unit(Tenant $tenant): InventoryUnit
    {
        return InventoryUnit::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Liter',
            'symbol' => 'l',
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

    private function login(User $user): void
    {
        $this->actingAs($user, 'web')->withSession([
            'tenant.authenticated_at' => now()->getTimestamp(),
            'tenant.last_activity_at' => now()->getTimestamp(),
        ]);
    }
}
