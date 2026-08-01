<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_sensitive_action_approvals', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('performer_user_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('approver_user_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->string('action', 80);
            $table->string('target_type', 80);
            $table->char('target_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('request_fingerprint', 64)->charset('ascii')->collation('ascii_bin');
            $table->string('status', 24);
            $table->text('reason');
            $table->text('decision_reason')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('performer_user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('approver_user_id')->references('id')->on('users')->restrictOnDelete();
            $table->index(['tenant_id', 'outlet_id', 'status'], 'sales_approval_scope_status_index');
            $table->index(['tenant_id', 'target_type', 'target_id'], 'sales_approval_target_index');
        });

        Schema::create('sales_audit_events', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->char('actor_user_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->string('event_type', 120);
            $table->string('target_type', 80)->nullable();
            $table->char('target_id', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->string('outcome', 40)->nullable();
            $table->text('reason')->nullable();
            $table->string('correlation_id', 120)->charset('ascii')->collation('ascii_bin');
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamp('created_at')->nullable();

            $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('actor_user_id')->references('id')->on('users')->restrictOnDelete();
            $table->index(['tenant_id', 'event_type', 'occurred_at'], 'sales_audit_event_type_index');
            $table->index(['tenant_id', 'target_type', 'target_id'], 'sales_audit_target_index');
        });

        Schema::create('sales_refunds', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('shift_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('order_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('payment_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('approval_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('refunded_by', 26)->charset('ascii')->collation('ascii_bin');
            $table->string('method', 32);
            $table->string('status', 24);
            $table->bigInteger('amount_minor');
            $table->char('currency', 3)->charset('ascii')->collation('ascii_bin');
            $table->text('reason');
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('shift_id')->references('id')->on('sales_shifts')->restrictOnDelete();
            $table->foreign('order_id')->references('id')->on('sales_orders')->restrictOnDelete();
            $table->foreign('payment_id')->references('id')->on('sales_payments')->restrictOnDelete();
            $table->foreign('approval_id')->references('id')->on('sales_sensitive_action_approvals')->restrictOnDelete();
            $table->foreign('refunded_by')->references('id')->on('users')->restrictOnDelete();
            $table->unique(['tenant_id', 'payment_id'], 'sales_refund_payment_unique');
            $table->index(['tenant_id', 'outlet_id', 'status'], 'sales_refund_scope_status_index');
            $table->index(['tenant_id', 'recorded_at'], 'sales_refund_recorded_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_refunds');
        Schema::dropIfExists('sales_audit_events');
        Schema::dropIfExists('sales_sensitive_action_approvals');
    }
};
