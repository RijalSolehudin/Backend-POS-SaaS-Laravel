<?php

declare(strict_types=1);

namespace App\Modules\PaymentsGateway\Application\Data;

final readonly class PaymentProviderIntent
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $provider,
        public string $providerIntentId,
        public string $status,
        public array $payload = [],
    ) {}
}
