<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipe_recipes', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->string('name', 160);
            $table->string('sku', 64);
            $table->string('status', 16);
            $table->boolean('requires_recipe')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'sku']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('recipe_versions', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('recipe_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->unsignedInteger('version_number');
            $table->string('status', 16);
            $table->decimal('yield_quantity', 15, 3)->default('1.000');
            $table->unsignedSmallInteger('yield_percent')->default(100);
            $table->bigInteger('cost_minor')->nullable();
            $table->char('currency', 3)->charset('ascii')->collation('ascii_bin');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('recipe_id')->references('id')->on('recipe_recipes')->cascadeOnDelete();
            $table->unique(['tenant_id', 'recipe_id', 'version_number'], 'recipe_version_number_unique');
            $table->index(['tenant_id', 'recipe_id', 'status']);
        });

        Schema::create('recipe_ingredients', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('recipe_version_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('inventory_item_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('unit_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->decimal('quantity', 15, 3);
            $table->bigInteger('unit_cost_minor_snapshot')->nullable();
            $table->bigInteger('total_cost_minor_snapshot')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('recipe_version_id')->references('id')->on('recipe_versions')->cascadeOnDelete();
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->restrictOnDelete();
            $table->foreign('unit_id')->references('id')->on('inventory_units')->restrictOnDelete();
            $table->unique(['tenant_id', 'recipe_version_id', 'inventory_item_id'], 'recipe_ingredient_item_unique');
        });

        Schema::create('recipe_variant_mappings', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('variant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('recipe_version_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->boolean('requires_recipe')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('variant_id')->references('id')->on('catalog_product_variants')->restrictOnDelete();
            $table->foreign('recipe_version_id')->references('id')->on('recipe_versions')->restrictOnDelete();
            $table->unique(['tenant_id', 'variant_id'], 'recipe_variant_mapping_unique');
        });

        Schema::create('recipe_sales_deductions', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('order_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('order_item_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('recipe_version_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->json('snapshot');
            $table->bigInteger('total_cost_minor');
            $table->char('currency', 3)->charset('ascii')->collation('ascii_bin');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('order_id')->references('id')->on('sales_orders')->restrictOnDelete();
            $table->foreign('order_item_id')->references('id')->on('sales_order_items')->restrictOnDelete();
            $table->foreign('recipe_version_id')->references('id')->on('recipe_versions')->restrictOnDelete();
            $table->unique(['tenant_id', 'order_item_id'], 'recipe_sales_deduction_order_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_sales_deductions');
        Schema::dropIfExists('recipe_variant_mappings');
        Schema::dropIfExists('recipe_ingredients');
        Schema::dropIfExists('recipe_versions');
        Schema::dropIfExists('recipe_recipes');
    }
};
