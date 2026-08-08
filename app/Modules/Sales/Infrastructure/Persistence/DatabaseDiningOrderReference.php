<?php

declare(strict_types=1);

namespace App\Modules\Sales\Infrastructure\Persistence;

use App\Modules\Sales\Application\Contracts\DiningOrderReference;
use App\Modules\Sales\Application\Data\DiningOrderSummary;
use App\Modules\Sales\Domain\Models\Order;

final class DatabaseDiningOrderReference implements DiningOrderReference
{
    public function order(string $tenantId, string $outletId, string $orderId): ?DiningOrderSummary
    {
        $order = Order::query()
            ->where('tenant_id', $tenantId)
            ->where('outlet_id', $outletId)
            ->whereKey($orderId)
            ->first();

        if (! $order instanceof Order) {
            return null;
        }

        return new DiningOrderSummary(
            orderId: $order->id,
            tenantId: $order->tenant_id,
            outletId: $order->outlet_id,
            status: $order->status->value,
        );
    }
}
