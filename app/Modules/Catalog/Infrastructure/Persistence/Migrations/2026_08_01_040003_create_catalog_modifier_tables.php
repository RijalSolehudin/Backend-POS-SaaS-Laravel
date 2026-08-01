<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_modifier_groups', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('product_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('variant_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->string('name', 120);
            $table->boolean('required')->default(false);
            $table->unsignedTinyInteger('min_selection')->default(0);
            $table->unsignedTinyInteger('max_selection')->default(1);
            $table->unsignedInteger('display_order')->default(0);
            $table->string('status', 16);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('catalog_products')->cascadeOnDelete();
            $table->foreign('variant_id')->references('id')->on('catalog_product_variants')->cascadeOnDelete();
            $table->index(['tenant_id', 'product_id', 'variant_id', 'status'], 'catalog_modifier_group_scope_index');
        });

        Schema::create('catalog_modifier_options', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('group_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->string('name', 120);
            $table->bigInteger('price_delta_minor')->default(0);
            $table->char('currency', 3)->charset('ascii')->collation('ascii_bin');
            $table->unsignedInteger('display_order')->default(0);
            $table->string('status', 16);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('group_id')->references('id')->on('catalog_modifier_groups')->cascadeOnDelete();
            $table->index(['tenant_id', 'group_id', 'status', 'display_order'], 'catalog_modifier_option_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_modifier_options');
        Schema::dropIfExists('catalog_modifier_groups');
    }
};
