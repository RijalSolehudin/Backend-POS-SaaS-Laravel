<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Application\Data;

final readonly class PurchaseReturnLineInput
{
    public function __construct(
        public string $goodsReceiptLineId,
        public string $quantity,
    ) {}
}
