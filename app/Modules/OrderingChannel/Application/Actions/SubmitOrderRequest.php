<?php

declare(strict_types=1);

namespace App\Modules\OrderingChannel\Application\Actions;

use App\Modules\OrderingChannel\Application\Exceptions\OrderingChannelException;
use App\Modules\OrderingChannel\Domain\Enums\OrderRequestStatus;
use App\Modules\OrderingChannel\Domain\Models\OrderingCustomerCart;
use App\Modules\OrderingChannel\Domain\Models\OrderingCustomerCartItem;
use App\Modules\OrderingChannel\Domain\Models\OrderingOrderRequest;
use Carbon\CarbonImmutable;

final readonly class SubmitOrderRequest
{
    public function handle(OrderingCustomerCart $cart, ?string $tableSessionId = null, ?CarbonImmutable $expiresAt = null): OrderingOrderRequest
    {
        if (! OrderingCustomerCartItem::query()->where('tenant_id', $cart->tenant_id)->where('cart_id', $cart->id)->exists()) {
            throw OrderingChannelException::cartInvalid();
        }

        return OrderingOrderRequest::query()->create([
            'tenant_id' => $cart->tenant_id,
            'outlet_id' => $cart->outlet_id,
            'cart_id' => $cart->id,
            'table_session_id' => $tableSessionId,
            'status' => OrderRequestStatus::Pending,
            'expires_at' => $expiresAt ?? CarbonImmutable::now()->addMinutes(30),
        ]);
    }
}
