<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Data;

final readonly class LowStockItemView
{
    public function __construct(
        public string $tenantId,
        public string $outletId,
        public string $itemId,
        public string $itemName,
        public string $sku,
        public string $unitSymbol,
        public string $quantity,
        public string $thresholdQuantity,
        public int $totalCostMinor,
        public string $currency,
    ) {}
}
