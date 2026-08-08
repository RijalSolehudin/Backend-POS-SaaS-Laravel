<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dining_table_sessions', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('table_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('previous_table_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->char('target_session_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->string('open_table_key', 96)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->unsignedSmallInteger('party_size')->default(1);
            $table->string('status', 24);
            $table->char('opened_by', 26)->charset('ascii')->collation('ascii_bin');
            $table->timestamp('opened_at');
            $table->char('closed_by', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->char('cancelled_by', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('transferred_at')->nullable();
            $table->timestamp('merged_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('table_id')->references('id')->on('dining_tables')->restrictOnDelete();
            $table->foreign('previous_table_id')->references('id')->on('dining_tables')->restrictOnDelete();
            $table->foreign('target_session_id')->references('id')->on('dining_table_sessions')->restrictOnDelete();
            $table->foreign('opened_by')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('closed_by')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('cancelled_by')->references('id')->on('users')->restrictOnDelete();
            $table->unique('open_table_key');
            $table->index(['tenant_id', 'outlet_id', 'status']);
            $table->index(['tenant_id', 'table_id', 'status']);
        });

        Schema::create('dining_table_session_orders', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('table_session_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('order_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('table_session_id')->references('id')->on('dining_table_sessions')->cascadeOnDelete();
            $table->foreign('order_id')->references('id')->on('sales_orders')->restrictOnDelete();
            $table->unique(['tenant_id', 'order_id'], 'dining_session_order_unique');
            $table->index(['tenant_id', 'outlet_id', 'table_session_id'], 'dining_session_order_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dining_table_session_orders');
        Schema::dropIfExists('dining_table_sessions');
    }
};
