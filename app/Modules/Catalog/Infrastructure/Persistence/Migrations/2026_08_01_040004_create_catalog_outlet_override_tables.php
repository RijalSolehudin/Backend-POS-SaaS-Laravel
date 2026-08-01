<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_variant_outlet_availabilities', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('variant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->boolean('available')->default(true);
            $table->bigInteger('price_minor')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('variant_id')->references('id')->on('catalog_product_variants')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->cascadeOnDelete();
            $table->unique(['tenant_id', 'variant_id', 'outlet_id'], 'catalog_variant_outlet_unique');
            $table->index(['tenant_id', 'outlet_id', 'available'], 'catalog_variant_outlet_lookup');
        });

        Schema::create('catalog_modifier_option_outlet_overrides', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('option_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->boolean('available')->default(true);
            $table->bigInteger('price_delta_minor')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('option_id')->references('id')->on('catalog_modifier_options')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->cascadeOnDelete();
            $table->unique(['tenant_id', 'option_id', 'outlet_id'], 'catalog_modifier_option_outlet_unique');
            $table->index(['tenant_id', 'outlet_id', 'available'], 'catalog_modifier_option_outlet_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_modifier_option_outlet_overrides');
        Schema::dropIfExists('catalog_variant_outlet_availabilities');
    }
};
