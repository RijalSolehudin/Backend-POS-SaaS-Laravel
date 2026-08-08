<?php

declare(strict_types=1);

namespace App\Modules\PaymentsGateway\Application\Contracts;

use App\Modules\PaymentsGateway\Application\Data\PaymentProviderIntent;
use App\Modules\PaymentsGateway\Application\Data\PaymentWebhookMessage;

interface PaymentProvider
{
    public function name(): string;

    public function createIntent(string $orderId, int $amountMinor, string $currency): PaymentProviderIntent;

    public function verifyWebhook(string $payload, string $signature): PaymentWebhookMessage;
}
