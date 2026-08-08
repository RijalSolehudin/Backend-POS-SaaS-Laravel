<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('table_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->char('table_session_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->string('customer_name', 120)->nullable();
            $table->string('customer_phone', 40)->nullable();
            $table->unsignedSmallInteger('party_size');
            $table->timestamp('reserved_at');
            $table->string('status', 24);
            $table->timestamp('seated_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('table_id')->references('id')->on('dining_tables')->restrictOnDelete();
            $table->foreign('table_session_id')->references('id')->on('dining_table_sessions')->restrictOnDelete();
            $table->index(['tenant_id', 'outlet_id', 'reserved_at', 'status'], 'reservation_schedule_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
