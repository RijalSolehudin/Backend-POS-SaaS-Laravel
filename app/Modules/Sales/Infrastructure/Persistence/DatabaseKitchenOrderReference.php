<?php

declare(strict_types=1);

namespace App\Modules\Sales\Infrastructure\Persistence;

use App\Modules\Sales\Application\Contracts\KitchenOrderReference;
use App\Modules\Sales\Application\Data\KitchenOrderItemSummary;
use App\Modules\Sales\Application\Data\KitchenOrderSummary;
use App\Modules\Sales\Domain\Models\Order;

final class DatabaseKitchenOrderReference implements KitchenOrderReference
{
    public function orderWithItems(string $tenantId, string $outletId, string $orderId): ?KitchenOrderSummary
    {
        $order = Order::query()
            ->with('items')
            ->where('tenant_id', $tenantId)
            ->where('outlet_id', $outletId)
            ->whereKey($orderId)
            ->first();

        if (! $order instanceof Order) {
            return null;
        }

        $items = [];

        foreach ($order->items as $item) {
            $items[] = new KitchenOrderItemSummary(
                itemId: $item->id,
                orderId: $item->order_id,
                tenantId: $item->tenant_id,
                outletId: $order->outlet_id,
                productId: $item->product_id,
                variantId: $item->variant_id,
                categoryId: $item->product_category_id,
                productName: $item->product_name,
                variantName: $item->variant_name,
                quantity: $item->quantity,
                currency: $item->currency,
            );
        }

        return new KitchenOrderSummary(
            orderId: $order->id,
            tenantId: $order->tenant_id,
            outletId: $order->outlet_id,
            orderNumber: $order->order_number,
            status: $order->status->value,
            items: $items,
        );
    }
}
