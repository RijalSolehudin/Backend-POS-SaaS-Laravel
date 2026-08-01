<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Data;

use Carbon\CarbonImmutable;

final readonly class StockCardEntry
{
    public function __construct(
        public string $movementId,
        public string $movementType,
        public string $sourceType,
        public ?string $sourceId,
        public string $quantity,
        public ?int $unitCostMinor,
        public int $totalCostMinor,
        public string $currency,
        public string $balanceQuantityAfter,
        public int $balanceTotalCostMinorAfter,
        public ?string $reason,
        public CarbonImmutable $occurredAt,
    ) {}
}
