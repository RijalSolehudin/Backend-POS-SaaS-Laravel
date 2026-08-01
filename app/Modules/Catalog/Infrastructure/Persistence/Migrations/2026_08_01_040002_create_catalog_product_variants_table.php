<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_product_variants', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('product_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->string('name', 120);
            $table->string('sku', 64);
            $table->bigInteger('price_minor');
            $table->char('currency', 3)->charset('ascii')->collation('ascii_bin');
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('display_order')->default(0);
            $table->string('status', 16);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('catalog_products')->cascadeOnDelete();
            $table->unique(['tenant_id', 'sku'], 'catalog_variant_tenant_sku_unique');
            $table->index(['tenant_id', 'product_id', 'status', 'display_order'], 'catalog_variant_product_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_product_variants');
    }
};
