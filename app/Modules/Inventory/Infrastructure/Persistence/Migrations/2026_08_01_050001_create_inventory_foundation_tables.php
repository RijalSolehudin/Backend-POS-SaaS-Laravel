<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_units', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->string('name', 120);
            $table->string('symbol', 24);
            $table->unsignedTinyInteger('precision')->default(3);
            $table->string('status', 16);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'symbol']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('inventory_items', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('unit_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->string('name', 160);
            $table->string('sku', 64);
            $table->string('status', 16);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('unit_id')->references('id')->on('inventory_units')->restrictOnDelete();
            $table->unique(['tenant_id', 'sku']);
            $table->index(['tenant_id', 'unit_id', 'status']);
        });

        Schema::create('inventory_item_outlet_settings', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('item_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->string('status', 16);
            $table->decimal('low_stock_threshold_quantity', 15, 3)->default('0.000');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->cascadeOnDelete();
            $table->foreign('item_id')->references('id')->on('inventory_items')->cascadeOnDelete();
            $table->unique(['tenant_id', 'outlet_id', 'item_id'], 'inventory_item_outlet_unique');
            $table->index(['tenant_id', 'outlet_id', 'status'], 'inventory_item_outlet_lookup');
        });

        Schema::create('inventory_audit_events', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->char('actor_user_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->string('event_type', 80);
            $table->string('target_type', 80)->nullable();
            $table->char('target_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->string('outcome', 32)->nullable();
            $table->string('reason', 255)->nullable();
            $table->uuid('correlation_id');
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->nullOnDelete();
            $table->index(['tenant_id', 'event_type', 'occurred_at'], 'inventory_audit_event_lookup');
            $table->index(['tenant_id', 'target_type', 'target_id'], 'inventory_audit_target_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_audit_events');
        Schema::dropIfExists('inventory_item_outlet_settings');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('inventory_units');
    }
};
