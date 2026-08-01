<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Data;

final readonly class InventoryTransferLineInput
{
    public function __construct(
        public string $itemId,
        public string $quantity,
    ) {}
}
