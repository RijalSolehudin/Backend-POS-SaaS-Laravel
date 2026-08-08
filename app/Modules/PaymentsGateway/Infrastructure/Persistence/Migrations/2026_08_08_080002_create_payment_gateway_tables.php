<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateway_intents', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->char('tenant_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('outlet_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('sales_order_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->char('user_id', 26)->charset('ascii')->collation('ascii_bin');
            $table->string('provider', 40);
            $table->string('provider_intent_id', 120)->charset('ascii')->collation('ascii_bin');
            $table->string('status', 24);
            $table->bigInteger('amount_minor');
            $table->char('currency', 3)->charset('ascii')->collation('ascii_bin');
            $table->json('provider_payload')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('sales_order_id')->references('id')->on('sales_orders')->restrictOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->unique(['provider', 'provider_intent_id'], 'payment_gateway_provider_intent_unique');
            $table->index(['tenant_id', 'outlet_id', 'sales_order_id', 'status'], 'payment_gateway_intent_lookup');
        });

        Schema::create('payment_gateway_webhook_events', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->ulid('id')->primary()->charset('ascii')->collation('ascii_bin');
            $table->string('provider', 40);
            $table->string('provider_event_id', 160)->charset('ascii')->collation('ascii_bin');
            $table->string('event_type', 80);
            $table->json('payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_event_id'], 'payment_gateway_webhook_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_webhook_events');
        Schema::dropIfExists('payment_gateway_intents');
    }
};
