<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Data;

final readonly class OpeningBalanceInput
{
    public function __construct(
        public string $outletId,
        public string $itemId,
        public string $quantity,
        public int $totalCostMinor,
        public string $currency,
        public ?string $reason,
    ) {}
}
