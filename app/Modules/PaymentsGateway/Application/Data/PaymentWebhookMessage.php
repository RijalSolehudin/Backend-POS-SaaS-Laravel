<?php

declare(strict_types=1);

namespace App\Modules\PaymentsGateway\Application\Data;

final readonly class PaymentWebhookMessage
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $provider,
        public string $eventId,
        public string $eventType,
        public ?string $providerIntentId,
        public array $payload,
    ) {}
}
