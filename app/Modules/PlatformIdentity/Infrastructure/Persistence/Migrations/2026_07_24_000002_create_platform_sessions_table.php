<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_sessions', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->string('id')->primary();
            $table->ulid('user_id')->nullable()->charset('ascii')->collation('ascii_bin')->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['user_id', 'last_activity']);

            $table->foreign('user_id')
                ->references('id')
                ->on('platform_users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_sessions');
    }
};
