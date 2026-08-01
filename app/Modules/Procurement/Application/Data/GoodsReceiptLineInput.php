<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Application\Data;

final readonly class GoodsReceiptLineInput
{
    public function __construct(
        public string $purchaseOrderLineId,
        public string $quantity,
    ) {}
}
