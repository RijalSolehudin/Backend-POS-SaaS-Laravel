<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_order_items', function (Blueprint $table): void {
            $table->char('variant_id', 26)->charset('ascii')->collation('ascii_bin')->nullable()->after('product_id');
            $table->string('variant_sku', 64)->nullable()->after('product_sku');
            $table->string('variant_name', 120)->nullable()->after('product_name');
            $table->bigInteger('modifier_total_minor')->default(0)->after('unit_price_minor');
            $table->json('modifier_snapshot')->nullable()->after('modifier_total_minor');

            $table->foreign('variant_id')->references('id')->on('catalog_product_variants')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales_order_items', function (Blueprint $table): void {
            $table->dropForeign(['variant_id']);
            $table->dropColumn([
                'variant_id',
                'variant_sku',
                'variant_name',
                'modifier_total_minor',
                'modifier_snapshot',
            ]);
        });
    }
};
