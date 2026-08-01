<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Application\Data;

final readonly class SupplierItemInput
{
    public function __construct(
        public string $supplierId,
        public string $inventoryItemId,
        public string $supplierSku,
        public int $lastPriceMinor,
        public string $currency,
        public bool $isActive = true,
    ) {}
}
