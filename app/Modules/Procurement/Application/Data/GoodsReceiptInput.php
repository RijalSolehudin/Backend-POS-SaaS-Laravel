<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Application\Data;

final readonly class GoodsReceiptInput
{
    /**
     * @param  list<GoodsReceiptLineInput>  $lines
     */
    public function __construct(
        public string $purchaseOrderId,
        public array $lines,
    ) {}
}
