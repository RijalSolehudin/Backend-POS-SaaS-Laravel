<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Application\Data;

final readonly class PurchaseOrderLineInput
{
    public function __construct(
        public string $supplierItemId,
        public string $quantity,
        public int $unitPriceMinor,
    ) {}
}
