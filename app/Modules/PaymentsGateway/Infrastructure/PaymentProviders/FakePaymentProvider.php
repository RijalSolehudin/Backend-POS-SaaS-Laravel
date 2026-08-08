<?php

declare(strict_types=1);

namespace App\Modules\PaymentsGateway\Infrastructure\PaymentProviders;

use App\Modules\PaymentsGateway\Application\Contracts\PaymentProvider;
use App\Modules\PaymentsGateway\Application\Data\PaymentProviderIntent;
use App\Modules\PaymentsGateway\Application\Data\PaymentWebhookMessage;
use App\Modules\PaymentsGateway\Application\Exceptions\PaymentGatewayException;
use Illuminate\Support\Str;

final class FakePaymentProvider implements PaymentProvider
{
    public function name(): string
    {
        return 'fake';
    }

    public function createIntent(string $orderId, int $amountMinor, string $currency): PaymentProviderIntent
    {
        return new PaymentProviderIntent(
            provider: $this->name(),
            providerIntentId: 'pi_'.strtolower((string) Str::ulid()),
            status: 'pending',
            payload: [
                'order_id' => $orderId,
                'amount_minor' => $amountMinor,
                'currency' => $currency,
            ],
        );
    }

    public function verifyWebhook(string $payload, string $signature): PaymentWebhookMessage
    {
        $expected = hash_hmac('sha256', $payload, (string) config('services.fake_payments.webhook_secret', 'local-secret'));

        if (! hash_equals($expected, $signature)) {
            throw PaymentGatewayException::signatureInvalid();
        }

        /** @var array{event_id: string, type: string, intent_id?: string|null} $decoded */
        $decoded = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);

        return new PaymentWebhookMessage(
            provider: $this->name(),
            eventId: $decoded['event_id'],
            eventType: $decoded['type'],
            providerIntentId: isset($decoded['intent_id']) ? (string) $decoded['intent_id'] : null,
            payload: $decoded,
        );
    }
}
