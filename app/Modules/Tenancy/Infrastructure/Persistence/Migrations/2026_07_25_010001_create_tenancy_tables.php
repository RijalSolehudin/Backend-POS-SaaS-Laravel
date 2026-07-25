<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->string('name', 160);
            $table->string('code', 64)->charset('ascii')->collation('ascii_bin')->unique();
            $table->char('currency', 3)->charset('ascii')->collation('ascii_bin');
            $table->string('timezone', 64);
            $table->string('status', 32)->index();
            $table->timestamp('disabled_at')->nullable();
            $table->text('disabled_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('outlets', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->string('name', 120);
            $table->string('code', 32)->charset('ascii')->collation('ascii_bin');
            $table->string('status', 32);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('tenant_memberships', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('user_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->string('membership_type', 32);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->unique('user_id');
            $table->unique(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'membership_type']);
        });

        Schema::create('tenant_provisioning_requests', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('idempotency_key', 26)->charset('ascii')->collation('ascii_bin')->unique();
            $table->char('input_hash', 64)->charset('ascii')->collation('ascii_bin');
            $table->string('status', 32)->index();
            $table->string('correlation_id', 64)->index();
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->char('owner_user_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->char('membership_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->char('role_assignment_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('owner_user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('membership_id')->references('id')->on('tenant_memberships')->restrictOnDelete();
            $table->foreign('role_assignment_id')->references('id')->on('user_role_assignments')->restrictOnDelete();
        });

        Schema::create('tenancy_audit_events', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->string('event_type', 80)->index();
            $table->string('outcome', 20)->index();
            $table->string('actor_type', 40);
            $table->string('actor_id', 254);
            $table->char('target_tenant_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->string('correlation_id', 64)->index();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['target_tenant_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenancy_audit_events');
        Schema::dropIfExists('tenant_provisioning_requests');
        Schema::dropIfExists('tenant_memberships');
        Schema::dropIfExists('outlets');
        Schema::dropIfExists('tenants');
    }
};
