<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_cash_movements', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('shift_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('user_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('approval_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->string('type', 24);
            $table->bigInteger('amount_minor');
            $table->char('currency', 3)->charset('ascii')->collation('ascii_bin');
            $table->text('reason');
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('shift_id')->references('id')->on('sales_shifts')->restrictOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('approval_id')->references('id')->on('sales_sensitive_action_approvals')->restrictOnDelete();
            $table->index(['tenant_id', 'outlet_id', 'shift_id'], 'sales_cash_movement_shift_index');
            $table->index(['tenant_id', 'recorded_at'], 'sales_cash_movement_recorded_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_cash_movements');
    }
};
