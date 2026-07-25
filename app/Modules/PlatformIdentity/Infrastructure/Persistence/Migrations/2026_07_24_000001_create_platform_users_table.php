<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_users', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->string('name', 120);
            $table->string('email', 254)->unique();
            $table->string('password');
            $table->string('status', 32)->index();
            $table->text('totp_secret')->nullable();
            $table->unsignedBigInteger('totp_last_used_step')->nullable();
            $table->timestamp('totp_confirmed_at')->nullable();
            $table->timestamp('password_changed_at');
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_users');
    }
};
