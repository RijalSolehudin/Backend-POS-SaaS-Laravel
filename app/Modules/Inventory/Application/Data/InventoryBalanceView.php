<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Data;

final readonly class InventoryBalanceView
{
    public function __construct(
        public string $tenantId,
        public string $outletId,
        public string $itemId,
        public string $unitId,
        public string $quantity,
        public int $totalCostMinor,
        public string $currency,
        public ?int $averageCostMinor,
        public string $inTransitQuantity,
    ) {}
}
