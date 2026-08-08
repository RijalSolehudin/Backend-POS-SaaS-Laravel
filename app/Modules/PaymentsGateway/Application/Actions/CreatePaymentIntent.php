<?php

declare(strict_types=1);

namespace App\Modules\PaymentsGateway\Application\Actions;

use App\Modules\PaymentsGateway\Application\Contracts\PaymentProvider;
use App\Modules\PaymentsGateway\Application\Exceptions\PaymentGatewayException;
use App\Modules\PaymentsGateway\Domain\Enums\PaymentIntentStatus;
use App\Modules\PaymentsGateway\Domain\Models\PaymentGatewayIntent;
use App\Modules\Sales\Domain\Enums\OrderStatus;
use App\Modules\Sales\Domain\Models\Order;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use Carbon\CarbonImmutable;

final readonly class CreatePaymentIntent
{
    public function __construct(
        private TenantPermissionGuard $permissions,
        private PaymentProvider $provider,
    ) {}

    public function handle(TenantRequestContext $context, string $outletId, string $orderId): PaymentGatewayIntent
    {
        $this->permissions->authorizeOperatePos($context, $outletId);
        $order = Order::query()
            ->where('tenant_id', $context->tenantId)
            ->where('outlet_id', $outletId)
            ->whereKey($orderId)
            ->firstOrFail();

        if ($order->status !== OrderStatus::Draft) {
            throw PaymentGatewayException::intentInvalidState();
        }

        $providerIntent = $this->provider->createIntent($order->id, $order->total_minor, $order->currency);

        return PaymentGatewayIntent::query()->create([
            'tenant_id' => $context->tenantId,
            'outlet_id' => $outletId,
            'sales_order_id' => $order->id,
            'user_id' => $context->userId,
            'provider' => $providerIntent->provider,
            'provider_intent_id' => $providerIntent->providerIntentId,
            'status' => PaymentIntentStatus::from($providerIntent->status),
            'amount_minor' => $order->total_minor,
            'currency' => $order->currency,
            'provider_payload' => $this->redact($providerIntent->payload),
            'expires_at' => CarbonImmutable::now()->addMinutes(30),
        ]);
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
