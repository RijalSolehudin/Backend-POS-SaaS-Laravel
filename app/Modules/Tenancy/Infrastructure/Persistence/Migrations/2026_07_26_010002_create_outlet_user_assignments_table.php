<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outlets', function (Blueprint $table): void {
            $table->unique(['tenant_id', 'id'], 'outlets_tenant_id_id_unique');
        });

        Schema::create('outlet_user_assignments', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('user_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->timestamps();

            $table->unique(['outlet_id', 'user_id']);
            $table->index(['tenant_id', 'user_id']);
            $table->foreign(['tenant_id', 'outlet_id'])
                ->references(['tenant_id', 'id'])
                ->on('outlets')
                ->restrictOnDelete();
            $table->foreign(['tenant_id', 'user_id'])
                ->references(['tenant_id', 'user_id'])
                ->on('tenant_memberships')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outlet_user_assignments');

        Schema::table('outlets', function (Blueprint $table): void {
            $table->dropUnique('outlets_tenant_id_id_unique');
        });
    }
};
