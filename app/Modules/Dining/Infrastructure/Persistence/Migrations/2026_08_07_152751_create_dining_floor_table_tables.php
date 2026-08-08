<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dining_floors', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->ulid('tenant_id')->charset('ascii')->collation('ascii_bin');
            $table->ulid('outlet_id')->charset('ascii')->collation('ascii_bin');
            $table->string('name', 120);
            $table->string('status', 16);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->index(['tenant_id', 'outlet_id'], 'dining_floor_outlet_lookup');
        });


        Schema::create('dining_tables', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->ulid('tenant_id')->charset('ascii')->collation('ascii_bin');
            $table->ulid('outlet_id')->charset('ascii')->collation('ascii_bin');
            $table->ulid('dining_floor_id')->charset('ascii')->collation('ascii_bin');
            $table->string('code', 40);
            $table->string('name', 120);
            $table->string('status', 16);
            $table->unsignedSmallInteger('seats')->default(2);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('dining_floor_id')->references('id')->on('dining_floors')->restrictOnDelete();
            $table->unique(['tenant_id', 'outlet_id', 'code'], 'dining_table_code_unique');
            $table->index(['tenant_id', 'outlet_id'], 'dining_table_outlet_lookup');
            $table->index(['tenant_id', 'outlet_id', 'dining_floor_id'], 'dining_table_floor_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dining_floors');
        Schema::dropIfExists('dining_tables');
    }
};
