<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Actions;

use App\Modules\Sales\Application\Exceptions\OrderException;
use App\Modules\Sales\Domain\Models\Order;
use App\Modules\Tenancy\Application\Data\PosOutletContext;

final readonly class GetDraftOrder
{
    public function handle(PosOutletContext $context, string $orderId): Order
    {
        $order = Order::query()
            ->with('items')
            ->where('tenant_id', $context->tenantId)
            ->where('outlet_id', $context->outletId)
            ->where('user_id', $context->userId)
            ->whereKey($orderId)
            ->first();

        if (! $order instanceof Order) {
            throw OrderException::notFound();
        }

        return $order;
    }
}
