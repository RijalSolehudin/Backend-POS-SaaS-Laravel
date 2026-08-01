<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_balances', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('item_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('unit_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->decimal('quantity', 15, 3)->default('0.000');
            $table->bigInteger('total_cost_minor')->default(0);
            $table->char('currency', 3)->charset('ascii')->collation('ascii_bin');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('item_id')->references('id')->on('inventory_items')->restrictOnDelete();
            $table->foreign('unit_id')->references('id')->on('inventory_units')->restrictOnDelete();
            $table->unique(['tenant_id', 'outlet_id', 'item_id'], 'inventory_balance_scope_unique');
            $table->index(['tenant_id', 'outlet_id'], 'inventory_balance_outlet_lookup');
        });

        Schema::create('inventory_stock_movements', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('item_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('unit_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('actor_user_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->string('movement_type', 40);
            $table->string('source_type', 80);
            $table->char('source_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->char('opening_balance_key', 80)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->decimal('quantity', 15, 3);
            $table->bigInteger('unit_cost_minor')->nullable();
            $table->bigInteger('total_cost_minor');
            $table->char('currency', 3)->charset('ascii')->collation('ascii_bin');
            $table->decimal('balance_quantity_after', 15, 3);
            $table->bigInteger('balance_total_cost_minor_after');
            $table->string('reason', 255)->nullable();
            $table->string('idempotency_key', 120)->charset('ascii')->collation('ascii_bin');
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('item_id')->references('id')->on('inventory_items')->restrictOnDelete();
            $table->foreign('unit_id')->references('id')->on('inventory_units')->restrictOnDelete();
            $table->index(['tenant_id', 'outlet_id', 'item_id', 'occurred_at'], 'inventory_stock_card_lookup');
            $table->index(['tenant_id', 'source_type', 'source_id'], 'inventory_movement_source_lookup');
            $table->unique('opening_balance_key', 'inventory_opening_balance_unique');
        });

        Schema::create('inventory_idempotency_records', function (Blueprint $table): void {
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
            $table->unique(['tenant_id', 'outlet_id', 'user_id', 'action', 'idempotency_key'], 'inventory_idempotency_scope_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_idempotency_records');
        Schema::dropIfExists('inventory_stock_movements');
        Schema::dropIfExists('inventory_balances');
    }
};
