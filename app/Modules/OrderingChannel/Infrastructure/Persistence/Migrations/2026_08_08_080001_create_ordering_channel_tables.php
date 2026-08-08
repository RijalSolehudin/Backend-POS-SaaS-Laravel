<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordering_qr_sessions', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('table_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->string('context_type', 24)->default('table');
            $table->char('token_hash', 64)->charset('ascii')->collation('ascii_bin')->unique();
            $table->string('status', 24);
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('table_id')->references('id')->on('dining_tables')->restrictOnDelete();
            $table->index(['tenant_id', 'outlet_id', 'status']);
        });

        Schema::create('ordering_customer_carts', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('qr_session_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->string('customer_name', 120)->nullable();
            $table->string('customer_phone', 40)->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('qr_session_id')->references('id')->on('ordering_qr_sessions')->cascadeOnDelete();
            $table->index(['tenant_id', 'outlet_id', 'qr_session_id'], 'ordering_cart_session_lookup');
        });

        Schema::create('ordering_customer_cart_items', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('cart_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('product_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('variant_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->decimal('quantity', 12, 3);
            $table->json('modifier_option_ids')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('cart_id')->references('id')->on('ordering_customer_carts')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('catalog_products')->restrictOnDelete();
            $table->foreign('variant_id')->references('id')->on('catalog_product_variants')->restrictOnDelete();
            $table->index(['tenant_id', 'cart_id']);
        });

        Schema::create('ordering_order_requests', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('cart_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('table_session_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->char('sales_order_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->string('status', 24);
            $table->string('idempotency_key', 120)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->timestamp('expires_at');
            $table->char('confirmed_by', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->char('rejected_by', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('cart_id')->references('id')->on('ordering_customer_carts')->cascadeOnDelete();
            $table->foreign('table_session_id')->references('id')->on('dining_table_sessions')->restrictOnDelete();
            $table->foreign('sales_order_id')->references('id')->on('sales_orders')->restrictOnDelete();
            $table->foreign('confirmed_by')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('rejected_by')->references('id')->on('users')->restrictOnDelete();
            $table->unique(['tenant_id', 'outlet_id', 'idempotency_key'], 'ordering_request_idempotency_unique');
            $table->index(['tenant_id', 'outlet_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordering_order_requests');
        Schema::dropIfExists('ordering_customer_cart_items');
        Schema::dropIfExists('ordering_customer_carts');
        Schema::dropIfExists('ordering_qr_sessions');
    }
};
