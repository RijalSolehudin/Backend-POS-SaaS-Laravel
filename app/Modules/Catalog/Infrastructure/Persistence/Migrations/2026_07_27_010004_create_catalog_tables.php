<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_categories', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->string('name', 120);
            $table->string('status', 16);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('catalog_products', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('category_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->string('name', 160);
            $table->string('sku', 64);
            $table->bigInteger('base_price_minor');
            $table->char('currency', 3)->charset('ascii')->collation('ascii_bin');
            $table->string('status', 16);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('category_id')->references('id')->on('catalog_categories')->restrictOnDelete();
            $table->unique(['tenant_id', 'sku']);
            $table->index(['tenant_id', 'category_id', 'status']);
        });

        Schema::create('catalog_product_outlet_availabilities', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('product_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->boolean('available')->default(true);
            $table->bigInteger('price_minor')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('catalog_products')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->cascadeOnDelete();
            $table->unique(['tenant_id', 'product_id', 'outlet_id'], 'catalog_product_outlet_unique');
            $table->index(['tenant_id', 'outlet_id', 'available'], 'catalog_product_outlet_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_product_outlet_availabilities');
        Schema::dropIfExists('catalog_products');
        Schema::dropIfExists('catalog_categories');
    }
};
