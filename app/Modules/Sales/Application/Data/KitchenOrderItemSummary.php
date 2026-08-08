<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Data;

final readonly class KitchenOrderItemSummary
{
    public function __construct(
        public string $itemId,
        public string $orderId,
        public string $tenantId,
        public string $outletId,
        public string $productId,
        public ?string $variantId,
        public string $categoryId,
        public string $productName,
        public ?string $variantName,
        public string $quantity,
        public string $currency,
    ) {}
}
