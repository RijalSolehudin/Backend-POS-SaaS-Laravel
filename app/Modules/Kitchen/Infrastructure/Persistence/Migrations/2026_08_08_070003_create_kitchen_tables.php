<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kitchen_stations', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->string('name', 120);
            $table->string('code', 32);
            $table->boolean('is_fallback')->default(false);
            $table->string('status', 16);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->cascadeOnDelete();
            $table->unique(['tenant_id', 'outlet_id', 'code']);
            $table->index(['tenant_id', 'outlet_id', 'status']);
            $table->index(['tenant_id', 'outlet_id', 'is_fallback']);
        });

        Schema::create('kitchen_routing_rules', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('station_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->string('rule_type', 24);
            $table->char('catalog_reference_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->unsignedSmallInteger('priority')->default(100);
            $table->string('status', 16);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->cascadeOnDelete();
            $table->foreign('station_id')->references('id')->on('kitchen_stations')->cascadeOnDelete();
            $table->unique(['tenant_id', 'outlet_id', 'rule_type', 'catalog_reference_id'], 'kitchen_routing_rule_unique');
            $table->index(['tenant_id', 'outlet_id', 'status', 'priority'], 'kitchen_routing_lookup');
        });

        Schema::create('kitchen_tickets', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('station_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('order_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->string('order_number', 48)->charset('ascii')->collation('ascii_bin');
            $table->string('status', 24);
            $table->char('last_actor_user_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->timestamp('last_state_changed_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('station_id')->references('id')->on('kitchen_stations')->restrictOnDelete();
            $table->foreign('order_id')->references('id')->on('sales_orders')->restrictOnDelete();
            $table->foreign('last_actor_user_id')->references('id')->on('users')->restrictOnDelete();
            $table->unique(['tenant_id', 'outlet_id', 'order_id', 'station_id'], 'kitchen_ticket_order_station_unique');
            $table->index(['tenant_id', 'outlet_id', 'station_id', 'status'], 'kitchen_ticket_kds_lookup');
        });

        Schema::create('kitchen_ticket_items', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('ticket_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('order_item_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('product_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('variant_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->string('product_name', 160);
            $table->string('variant_name', 120)->nullable();
            $table->decimal('quantity', 12, 3);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('ticket_id')->references('id')->on('kitchen_tickets')->cascadeOnDelete();
            $table->foreign('order_item_id')->references('id')->on('sales_order_items')->restrictOnDelete();
            $table->foreign('product_id')->references('id')->on('catalog_products')->restrictOnDelete();
            $table->foreign('variant_id')->references('id')->on('catalog_product_variants')->restrictOnDelete();
            $table->unique(['tenant_id', 'outlet_id', 'order_item_id', 'ticket_id'], 'kitchen_ticket_item_unique');
            $table->index(['tenant_id', 'ticket_id']);
        });

        Schema::create('kitchen_ticket_events', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('ticket_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->string('event_type', 80);
            $table->char('actor_user_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('ticket_id')->references('id')->on('kitchen_tickets')->cascadeOnDelete();
            $table->foreign('actor_user_id')->references('id')->on('users')->restrictOnDelete();
            $table->index(['tenant_id', 'outlet_id', 'ticket_id', 'occurred_at'], 'kitchen_ticket_event_lookup');
        });

        Schema::create('kitchen_print_jobs', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('ticket_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('source_print_job_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->string('job_type', 24);
            $table->string('status', 24);
            $table->char('requested_by', 26)->charset('ascii')->collation('ascii_bin');
            $table->text('reason')->nullable();
            $table->json('payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('ticket_id')->references('id')->on('kitchen_tickets')->restrictOnDelete();
            $table->foreign('source_print_job_id')->references('id')->on('kitchen_print_jobs')->restrictOnDelete();
            $table->foreign('requested_by')->references('id')->on('users')->restrictOnDelete();
            $table->index(['tenant_id', 'outlet_id', 'ticket_id', 'status'], 'kitchen_print_job_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kitchen_print_jobs');
        Schema::dropIfExists('kitchen_ticket_events');
        Schema::dropIfExists('kitchen_ticket_items');
        Schema::dropIfExists('kitchen_tickets');
        Schema::dropIfExists('kitchen_routing_rules');
        Schema::dropIfExists('kitchen_stations');
    }
};
