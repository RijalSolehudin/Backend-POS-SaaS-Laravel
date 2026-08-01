<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_suppliers', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->string('name', 160);
            $table->string('code', 64);
            $table->string('status', 16);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'code'], 'procurement_supplier_code_unique');
            $table->index(['tenant_id', 'status'], 'procurement_supplier_status_lookup');
        });

        Schema::create('procurement_supplier_items', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('supplier_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('inventory_item_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->string('supplier_sku', 80);
            $table->bigInteger('last_price_minor')->default(0);
            $table->char('currency', 3)->charset('ascii')->collation('ascii_bin');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('supplier_id')->references('id')->on('procurement_suppliers')->cascadeOnDelete();
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->restrictOnDelete();
            $table->unique(['tenant_id', 'supplier_id', 'supplier_sku'], 'procurement_supplier_item_sku_unique');
            $table->unique(['tenant_id', 'supplier_id', 'inventory_item_id'], 'procurement_supplier_inventory_item_unique');
        });

        Schema::create('procurement_purchase_orders', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('supplier_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->string('po_number', 80);
            $table->string('status', 32);
            $table->bigInteger('total_minor')->default(0);
            $table->char('currency', 3)->charset('ascii')->collation('ascii_bin');
            $table->text('notes')->nullable();
            $table->char('created_by_user_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('submitted_by_user_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->char('approved_by_user_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->char('cancelled_by_user_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('ordered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('supplier_id')->references('id')->on('procurement_suppliers')->restrictOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('submitted_by_user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('approved_by_user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('cancelled_by_user_id')->references('id')->on('users')->restrictOnDelete();
            $table->unique(['tenant_id', 'po_number'], 'procurement_po_number_unique');
            $table->index(['tenant_id', 'outlet_id', 'status'], 'procurement_po_status_lookup');
        });

        Schema::create('procurement_purchase_order_lines', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('purchase_order_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('supplier_item_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('inventory_item_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('unit_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->decimal('quantity', 15, 3);
            $table->decimal('received_quantity', 15, 3)->default('0.000');
            $table->bigInteger('unit_price_minor');
            $table->bigInteger('line_total_minor');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('purchase_order_id')->references('id')->on('procurement_purchase_orders')->cascadeOnDelete();
            $table->foreign('supplier_item_id')->references('id')->on('procurement_supplier_items')->restrictOnDelete();
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->restrictOnDelete();
            $table->foreign('unit_id')->references('id')->on('inventory_units')->restrictOnDelete();
            $table->index(['tenant_id', 'purchase_order_id'], 'procurement_po_line_lookup');
        });

        Schema::create('procurement_goods_receipts', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('purchase_order_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->string('receipt_number', 80);
            $table->string('status', 16);
            $table->char('received_by_user_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->timestamp('received_at');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('purchase_order_id')->references('id')->on('procurement_purchase_orders')->restrictOnDelete();
            $table->foreign('received_by_user_id')->references('id')->on('users')->restrictOnDelete();
            $table->unique(['tenant_id', 'receipt_number'], 'procurement_receipt_number_unique');
            $table->index(['tenant_id', 'purchase_order_id'], 'procurement_receipt_po_lookup');
        });

        Schema::create('procurement_goods_receipt_lines', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('goods_receipt_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('purchase_order_line_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('inventory_item_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('unit_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->decimal('quantity', 15, 3);
            $table->decimal('returned_quantity', 15, 3)->default('0.000');
            $table->bigInteger('unit_cost_minor');
            $table->bigInteger('total_cost_minor');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('goods_receipt_id')->references('id')->on('procurement_goods_receipts')->cascadeOnDelete();
            $table->foreign('purchase_order_line_id')->references('id')->on('procurement_purchase_order_lines')->restrictOnDelete();
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->restrictOnDelete();
            $table->foreign('unit_id')->references('id')->on('inventory_units')->restrictOnDelete();
            $table->index(['tenant_id', 'goods_receipt_id'], 'procurement_receipt_line_lookup');
        });

        Schema::create('procurement_purchase_returns', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('goods_receipt_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->string('return_number', 80);
            $table->string('status', 16);
            $table->char('returned_by_user_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->string('reason', 255);
            $table->timestamp('returned_at');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('goods_receipt_id')->references('id')->on('procurement_goods_receipts')->restrictOnDelete();
            $table->foreign('returned_by_user_id')->references('id')->on('users')->restrictOnDelete();
            $table->unique(['tenant_id', 'return_number'], 'procurement_return_number_unique');
        });

        Schema::create('procurement_purchase_return_lines', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('purchase_return_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('goods_receipt_line_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('inventory_item_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('unit_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->decimal('quantity', 15, 3);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('purchase_return_id')->references('id')->on('procurement_purchase_returns')->cascadeOnDelete();
            $table->foreign('goods_receipt_line_id')->references('id')->on('procurement_goods_receipt_lines')->restrictOnDelete();
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->restrictOnDelete();
            $table->foreign('unit_id')->references('id')->on('inventory_units')->restrictOnDelete();
        });

        Schema::create('procurement_idempotency_records', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('user_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->string('action', 80);
            $table->string('idempotency_key', 120)->charset('ascii')->collation('ascii_bin');
            $table->char('request_hash', 64)->charset('ascii')->collation('ascii_bin');
            $table->string('resource_type', 80)->nullable();
            $table->char('resource_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->json('response_body')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->unique(['tenant_id', 'outlet_id', 'user_id', 'action', 'idempotency_key'], 'procurement_idempotency_scope_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_idempotency_records');
        Schema::dropIfExists('procurement_purchase_return_lines');
        Schema::dropIfExists('procurement_purchase_returns');
        Schema::dropIfExists('procurement_goods_receipt_lines');
        Schema::dropIfExists('procurement_goods_receipts');
        Schema::dropIfExists('procurement_purchase_order_lines');
        Schema::dropIfExists('procurement_purchase_orders');
        Schema::dropIfExists('procurement_supplier_items');
        Schema::dropIfExists('procurement_suppliers');
    }
};
