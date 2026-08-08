<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Data;

final readonly class KitchenOrderSummary
{
    /**
     * @param  list<KitchenOrderItemSummary>  $items
     */
    public function __construct(
        public string $orderId,
        public string $tenantId,
        public string $outletId,
        public string $orderNumber,
        public string $status,
        public array $items,
    ) {}
}
