<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_device_states', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('device_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->unsignedBigInteger('last_accepted_sequence')->default(0);
            $table->char('last_outbox_cursor', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('device_id')->references('id')->on('pos_devices')->cascadeOnDelete();
            $table->unique(['tenant_id', 'outlet_id', 'device_id'], 'sync_device_state_unique');
        });

        Schema::create('sync_inbox_records', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('device_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->string('client_record_id', 120)->charset('ascii')->collation('ascii_bin');
            $table->string('action', 80);
            $table->unsignedBigInteger('sequence_number');
            $table->string('idempotency_key', 120)->charset('ascii')->collation('ascii_bin');
            $table->char('payload_hash', 64)->charset('ascii')->collation('ascii_bin');
            $table->string('status', 24);
            $table->string('resource_type', 80)->nullable();
            $table->char('resource_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->json('payload')->nullable();
            $table->json('response')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('device_id')->references('id')->on('pos_devices')->cascadeOnDelete();
            $table->unique(['tenant_id', 'outlet_id', 'device_id', 'action', 'client_record_id', 'sequence_number'], 'sync_inbox_scope_unique');
            $table->index(['tenant_id', 'outlet_id', 'status']);
        });

        Schema::create('sync_outbox_records', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->string('event_type', 80);
            $table->string('resource_type', 80)->nullable();
            $table->char('resource_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->index(['tenant_id', 'outlet_id', 'id'], 'sync_outbox_cursor_lookup');
        });

        Schema::create('offline_order_drafts', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('device_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->string('client_order_id', 120)->charset('ascii')->collation('ascii_bin');
            $table->char('sales_order_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->string('status', 24);
            $table->json('draft_payload')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('device_id')->references('id')->on('pos_devices')->cascadeOnDelete();
            $table->foreign('sales_order_id')->references('id')->on('sales_orders')->restrictOnDelete();
            $table->unique(['tenant_id', 'outlet_id', 'device_id', 'client_order_id'], 'offline_order_client_unique');
            $table->index(['tenant_id', 'outlet_id', 'status']);
        });

        Schema::create('offline_order_events', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('offline_order_draft_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->string('event_type', 80);
            $table->unsignedBigInteger('sequence_number');
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('offline_order_draft_id')->references('id')->on('offline_order_drafts')->cascadeOnDelete();
            $table->unique(['offline_order_draft_id', 'event_type', 'sequence_number'], 'offline_order_event_unique');
        });

        Schema::create('sync_conflicts', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('device_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->char('sync_inbox_record_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->string('conflict_type', 80);
            $table->string('status', 24);
            $table->json('payload')->nullable();
            $table->char('resolved_by', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->text('resolution_reason')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('device_id')->references('id')->on('pos_devices')->nullOnDelete();
            $table->foreign('sync_inbox_record_id')->references('id')->on('sync_inbox_records')->nullOnDelete();
            $table->foreign('resolved_by')->references('id')->on('users')->restrictOnDelete();
            $table->index(['tenant_id', 'outlet_id', 'status']);
        });

        Schema::create('performance_baselines', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->string('baseline_type', 80);
            $table->unsignedInteger('target_p95_ms');
            $table->unsignedInteger('measured_p95_ms');
            $table->string('status', 24);
            $table->json('metadata')->nullable();
            $table->timestamp('measured_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_baselines');
        Schema::dropIfExists('sync_conflicts');
        Schema::dropIfExists('offline_order_events');
        Schema::dropIfExists('offline_order_drafts');
        Schema::dropIfExists('sync_outbox_records');
        Schema::dropIfExists('sync_inbox_records');
        Schema::dropIfExists('sync_device_states');
    }
};
