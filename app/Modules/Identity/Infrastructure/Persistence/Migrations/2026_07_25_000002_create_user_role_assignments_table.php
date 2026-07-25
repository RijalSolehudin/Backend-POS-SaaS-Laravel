<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_role_assignments', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('user_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->string('role', 40);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['user_id', 'role']);
            $table->index(['role', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_role_assignments');
    }
};
