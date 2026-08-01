<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Data;

final readonly class InventoryItemInput
{
    public function __construct(
        public string $unitId,
        public string $name,
        public string $sku,
    ) {}
}
