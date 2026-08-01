<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Data;

use App\Modules\Inventory\Domain\Enums\InventoryStatus;

final readonly class InventoryItemOutletSettingsInput
{
    public function __construct(
        public string $outletId,
        public string $itemId,
        public InventoryStatus $status,
        public string $lowStockThresholdQuantity,
    ) {}
}
