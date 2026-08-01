<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Data;

use App\Modules\Inventory\Domain\Enums\StockMovementType;

final readonly class StockAdjustmentInput
{
    public function __construct(
        public string $outletId,
        public string $itemId,
        public StockMovementType $movementType,
        public string $quantity,
        public ?int $totalCostMinor,
        public string $currency,
        public string $reason,
        public ?string $approvalId,
    ) {}
}
