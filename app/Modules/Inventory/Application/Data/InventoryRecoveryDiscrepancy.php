<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Data;

final readonly class InventoryRecoveryDiscrepancy
{
    public function __construct(
        public string $tenantId,
        public string $outletId,
        public string $itemId,
        public string $expectedQuantity,
        public string $actualQuantity,
        public int $expectedTotalCostMinor,
        public int $actualTotalCostMinor,
        public string $inTransitQuantity,
    ) {}
}
