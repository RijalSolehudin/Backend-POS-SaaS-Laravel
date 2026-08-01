<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Data;

final readonly class WasteInput
{
    public function __construct(
        public string $outletId,
        public string $itemId,
        public string $quantity,
        public string $currency,
        public string $reason,
        public ?string $approvalId,
    ) {}
}
