<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_shifts', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('user_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->string('open_shift_key', 96)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->string('status', 24);
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->char('voided_by', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->text('void_reason')->nullable();
            $table->bigInteger('opening_cash_minor')->default(0);
            $table->bigInteger('closing_cash_minor')->nullable();
            $table->bigInteger('expected_cash_minor')->default(0);
            $table->bigInteger('gross_sales_minor')->default(0);
            $table->char('currency', 3)->charset('ascii')->collation('ascii_bin');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('voided_by')->references('id')->on('users')->restrictOnDelete();
            $table->unique('open_shift_key');
            $table->index(['tenant_id', 'outlet_id', 'status']);
            $table->index(['tenant_id', 'user_id', 'status']);
        });

        Schema::create('sales_order_number_counters', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->date('business_date');
            $table->unsignedInteger('next_sequence')->default(1);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->unique(['tenant_id', 'outlet_id', 'business_date'], 'sales_order_counter_scope_unique');
        });

        Schema::create('sales_orders', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('shift_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('user_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->string('order_number', 48)->charset('ascii')->collation('ascii_bin');
            $table->string('status', 24);
            $table->bigInteger('subtotal_minor')->default(0);
            $table->bigInteger('discount_minor')->default(0);
            $table->bigInteger('service_charge_minor')->default(0);
            $table->bigInteger('tax_minor')->default(0);
            $table->bigInteger('total_minor')->default(0);
            $table->char('currency', 3)->charset('ascii')->collation('ascii_bin');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->char('cancelled_by', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->char('voided_by', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->text('void_reason')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('shift_id')->references('id')->on('sales_shifts')->restrictOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('cancelled_by')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('voided_by')->references('id')->on('users')->restrictOnDelete();
            $table->unique(['tenant_id', 'outlet_id', 'order_number'], 'sales_order_number_scope_unique');
            $table->index(['tenant_id', 'outlet_id', 'status']);
            $table->index(['tenant_id', 'shift_id', 'status']);
        });

        Schema::create('sales_order_items', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('order_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('product_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->string('product_sku', 64);
            $table->string('product_name', 160);
            $table->decimal('quantity', 12, 3);
            $table->bigInteger('unit_price_minor');
            $table->bigInteger('line_subtotal_minor');
            $table->char('currency', 3)->charset('ascii')->collation('ascii_bin');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
            $table->foreign('order_id')->references('id')->on('sales_orders')->restrictOnDelete();
            $table->foreign('product_id')->references('id')->on('catalog_products')->restrictOnDelete();
            $table->index(['tenant_id', 'order_id']);
        });

        Schema::create('sales_payments', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('shift_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('order_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->string('method', 32);
            $table->string('status', 24);
            $table->bigInteger('amount_minor');
            $table->char('currency', 3)->charset('ascii')->collation('ascii_bin');
            $table->timestamp('recorded_at');
            $table->timestamp('voided_at')->nullable();
            $table->char('voided_by', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->text('void_reason')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('shift_id')->references('id')->on('sales_shifts')->restrictOnDelete();
            $table->foreign('order_id')->references('id')->on('sales_orders')->restrictOnDelete();
            $table->foreign('voided_by')->references('id')->on('users')->restrictOnDelete();
            $table->index(['tenant_id', 'outlet_id', 'status']);
            $table->index(['tenant_id', 'order_id']);
        });

        Schema::create('sales_idempotency_records', function (Blueprint $table): void {
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
            $table->unique(['tenant_id', 'outlet_id', 'user_id', 'action', 'idempotency_key'], 'sales_idempotency_scope_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_idempotency_records');
        Schema::dropIfExists('sales_payments');
        Schema::dropIfExists('sales_order_items');
        Schema::dropIfExists('sales_orders');
        Schema::dropIfExists('sales_order_number_counters');
        Schema::dropIfExists('sales_shifts');
    }
};
