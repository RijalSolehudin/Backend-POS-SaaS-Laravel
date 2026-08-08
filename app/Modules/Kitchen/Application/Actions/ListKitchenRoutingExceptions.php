<?php

declare(strict_types=1);

namespace App\Modules\Kitchen\Application\Actions;

use App\Modules\Sales\Application\Contracts\KitchenOrderReference;
use App\Modules\Sales\Application\Data\KitchenOrderItemSummary;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;

final readonly class ListKitchenRoutingExceptions
{
    public function __construct(
        private KitchenOrderReference $orders,
        private ResolveKitchenRouting $routing,
    ) {}

    /**
     * @return list<KitchenOrderItemSummary>
     */
    public function handle(TenantRequestContext $context, string $outletId, string $orderId): array
    {
        $order = $this->orders->orderWithItems($context->tenantId, $outletId, $orderId);

        if ($order === null) {
            return [];
        }

        $missing = [];

        foreach ($order->items as $item) {
            $result = $this->routing->handle($context, $outletId, $item->productId, $item->variantId, $item->categoryId);

            if ($result->missingRouting) {
                $missing[] = $item;
            }
        }

        return $missing;
    }
}
