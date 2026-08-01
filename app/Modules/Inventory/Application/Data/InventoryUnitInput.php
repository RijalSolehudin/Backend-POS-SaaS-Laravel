<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Data;

final readonly class InventoryUnitInput
{
    public function __construct(
        public string $name,
        public string $symbol,
        public int $precision,
    ) {}
}
