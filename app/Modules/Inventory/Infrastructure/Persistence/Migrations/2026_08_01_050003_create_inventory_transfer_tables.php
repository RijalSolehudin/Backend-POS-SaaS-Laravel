<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transfers', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('source_outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('destination_outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('requested_by_user_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('approval_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->char('dispatched_by_user_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->char('received_by_user_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->char('cancelled_by_user_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->string('status', 32);
            $table->string('reason', 255);
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('source_outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('destination_outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('requested_by_user_id')->references('id')->on('users')->restrictOnDelete();
            $table->index(['tenant_id', 'source_outlet_id', 'status'], 'inventory_transfer_source_lookup');
            $table->index(['tenant_id', 'destination_outlet_id', 'status'], 'inventory_transfer_destination_lookup');
        });

        Schema::create('inventory_transfer_lines', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('transfer_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('item_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('unit_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->decimal('quantity', 15, 3);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('transfer_id')->references('id')->on('inventory_transfers')->cascadeOnDelete();
            $table->foreign('item_id')->references('id')->on('inventory_items')->restrictOnDelete();
            $table->foreign('unit_id')->references('id')->on('inventory_units')->restrictOnDelete();
            $table->unique(['tenant_id', 'transfer_id', 'item_id'], 'inventory_transfer_line_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transfer_lines');
        Schema::dropIfExists('inventory_transfers');
    }
};
