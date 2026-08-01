<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Data;

final readonly class InventoryTransferInput
{
    /**
     * @param  list<InventoryTransferLineInput>  $lines
     */
    public function __construct(
        public string $sourceOutletId,
        public string $destinationOutletId,
        public string $reason,
        public array $lines,
    ) {}
}
