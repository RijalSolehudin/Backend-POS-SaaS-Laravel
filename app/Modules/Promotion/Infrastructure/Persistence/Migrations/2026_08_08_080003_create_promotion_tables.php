<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_rules', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->string('name', 120);
            $table->string('code', 64);
            $table->string('discount_type', 24);
            $table->unsignedInteger('discount_value');
            $table->string('status', 16);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->cascadeOnDelete();
            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'outlet_id', 'status']);
        });

        Schema::create('sales_order_discounts', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('sales_order_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('promotion_rule_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->string('promotion_name', 120);
            $table->string('promotion_type', 24);
            $table->unsignedInteger('promotion_value');
            $table->bigInteger('discount_amount_minor');
            $table->string('source', 40);
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('sales_order_id')->references('id')->on('sales_orders')->restrictOnDelete();
            $table->foreign('promotion_rule_id')->references('id')->on('promotion_rules')->restrictOnDelete();
            $table->unique(['tenant_id', 'sales_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_discounts');
        Schema::dropIfExists('promotion_rules');
    }
};
