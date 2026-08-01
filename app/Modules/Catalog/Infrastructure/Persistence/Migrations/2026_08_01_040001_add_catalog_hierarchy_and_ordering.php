<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_categories', function (Blueprint $table): void {
            $table->char('parent_id', 26)
                ->charset('ascii')
                ->collation('ascii_bin')
                ->nullable()
                ->after('tenant_id');
            $table->unsignedInteger('display_order')->default(0)->after('name');

            $table->foreign('parent_id')->references('id')->on('catalog_categories')->restrictOnDelete();
            $table->index(['tenant_id', 'parent_id', 'display_order'], 'catalog_category_tree_order_index');
        });

        Schema::table('catalog_products', function (Blueprint $table): void {
            $table->unsignedInteger('display_order')->default(0)->after('currency');
            $table->index(['tenant_id', 'category_id', 'display_order'], 'catalog_product_display_order_index');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_products', function (Blueprint $table): void {
            $table->dropIndex('catalog_product_display_order_index');
            $table->dropColumn('display_order');
        });

        Schema::table('catalog_categories', function (Blueprint $table): void {
            $table->dropForeign(['parent_id']);
            $table->dropIndex('catalog_category_tree_order_index');
            $table->dropColumn(['parent_id', 'display_order']);
        });
    }
};
