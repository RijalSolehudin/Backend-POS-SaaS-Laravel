<?php

declare(strict_types=1);

namespace App\Modules\PaymentsGateway\Application\Actions;

use App\Modules\PaymentsGateway\Application\Contracts\PaymentProvider;
use App\Modules\PaymentsGateway\Domain\Enums\PaymentIntentStatus;
use App\Modules\PaymentsGateway\Domain\Models\PaymentGatewayIntent;
use App\Modules\PaymentsGateway\Domain\Models\PaymentGatewayWebhookEvent;
use App\Modules\Sales\Application\Actions\CompleteOrderWithPayment;
use App\Modules\Sales\Domain\Enums\PaymentMethod;
use App\Modules\Tenancy\Application\Data\PosOutletContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class HandlePaymentWebhook
{
    public function __construct(
        private PaymentProvider $provider,
        private CompleteOrderWithPayment $completeOrder,
    ) {}

    public function handle(string $payload, string $signature): PaymentGatewayWebhookEvent
    {
        $message = $this->provider->verifyWebhook($payload, $signature);

        return DB::transaction(function () use ($message): PaymentGatewayWebhookEvent {
            $existing = PaymentGatewayWebhookEvent::query()
                ->where('provider', $message->provider)
                ->where('provider_event_id', $message->eventId)
                ->first();

            if ($existing instanceof PaymentGatewayWebhookEvent) {
                return $existing;
            }

            $event = PaymentGatewayWebhookEvent::query()->create([
                'provider' => $message->provider,
                'provider_event_id' => $message->eventId,
                'event_type' => $message->eventType,
                'payload' => $this->redact($message->payload),
            ]);

            if ($message->providerIntentId !== null) {
                $intent = PaymentGatewayIntent::query()
                    ->where('provider', $message->provider)
                    ->where('provider_intent_id', $message->providerIntentId)
                    ->lockForUpdate()
                    ->first();

                if ($intent instanceof PaymentGatewayIntent) {
                    $this->applyIntentEvent($intent, $message->eventType);
                }
            }

            $event->forceFill(['processed_at' => CarbonImmutable::now()])->save();

            return $event;
        });
    }

    private function applyIntentEvent(PaymentGatewayIntent $intent, string $eventType): void
    {
        if ($intent->status === PaymentIntentStatus::Paid) {
            return;
        }

        if ($eventType === 'payment_intent.paid') {
            $intent->forceFill([
                'status' => PaymentIntentStatus::Paid,
                'paid_at' => CarbonImmutable::now(),
            ])->save();

            $this->completeOrder->handle(
                new PosOutletContext($intent->tenant_id, $intent->outlet_id, 'payment-gateway', $intent->user_id),
                $intent->sales_order_id,
                PaymentMethod::ManualNonCash,
                $intent->amount_minor,
                $intent->currency,
                'gateway-'.$intent->id,
            );

            return;
        }

        if ($eventType === 'payment_intent.failed') {
            $intent->forceFill(['status' => PaymentIntentStatus::Failed])->save();
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function redact(array $payload): array
    {
        unset($payload['card'], $payload['card_number'], $payload['cvv']);

        return $payload;
    }
}
