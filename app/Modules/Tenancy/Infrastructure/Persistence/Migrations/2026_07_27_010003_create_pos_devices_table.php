<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_devices', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('installation_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->string('name', 120);
            $table->string('client_type', 40)->default('pos_terminal');
            $table->string('platform', 40);
            $table->string('app_version', 40)->nullable();
            $table->string('status', 32)->index();
            $table->char('registered_by', 26)->charset('ascii')->collation('ascii_bin');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->char('revoked_by', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->text('revoked_reason')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'installation_id']);
            $table->index(['tenant_id', 'outlet_id', 'status']);
            $table->foreign(['tenant_id', 'outlet_id'])
                ->references(['tenant_id', 'id'])
                ->on('outlets')
                ->restrictOnDelete();
            $table->foreign(['tenant_id', 'registered_by'])
                ->references(['tenant_id', 'user_id'])
                ->on('tenant_memberships')
                ->restrictOnDelete();
            $table->foreign(['tenant_id', 'revoked_by'])
                ->references(['tenant_id', 'user_id'])
                ->on('tenant_memberships')
                ->restrictOnDelete();
        });

        Schema::table('personal_access_tokens', function (Blueprint $table): void {
            $table->char('pos_device_id', 26)->charset('ascii')->collation('ascii_bin')->nullable()->after('tokenable_id');
            $table->index(['pos_device_id', 'tokenable_id']);
            $table->foreign('pos_device_id')->references('id')->on('pos_devices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table): void {
            $table->dropForeign(['pos_device_id']);
            $table->dropIndex(['pos_device_id', 'tokenable_id']);
            $table->dropColumn('pos_device_id');
        });

        Schema::dropIfExists('pos_devices');
    }
};
