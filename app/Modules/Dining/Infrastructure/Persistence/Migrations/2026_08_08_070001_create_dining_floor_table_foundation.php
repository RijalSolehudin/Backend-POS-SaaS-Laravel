<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dining_floors', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->string('name', 120);
            $table->string('code', 32);
            $table->unsignedInteger('display_order')->default(0);
            $table->string('status', 16);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->cascadeOnDelete();
            $table->unique(['tenant_id', 'outlet_id', 'code']);
            $table->index(['tenant_id', 'outlet_id', 'status']);
        });

        Schema::create('dining_tables', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('floor_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->string('name', 120);
            $table->string('code', 32);
            $table->unsignedSmallInteger('capacity')->default(1);
            $table->unsignedInteger('display_order')->default(0);
            $table->string('status', 16);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->cascadeOnDelete();
            $table->foreign('floor_id')->references('id')->on('dining_floors')->restrictOnDelete();
            $table->unique(['tenant_id', 'outlet_id', 'code']);
            $table->index(['tenant_id', 'outlet_id', 'floor_id', 'status'], 'dining_tables_outlet_floor_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dining_tables');
        Schema::dropIfExists('dining_floors');
    }
};
