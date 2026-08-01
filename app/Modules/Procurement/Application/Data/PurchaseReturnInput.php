<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Application\Data;

final readonly class PurchaseReturnInput
{
    /**
     * @param  list<PurchaseReturnLineInput>  $lines
     */
    public function __construct(
        public string $goodsReceiptId,
        public string $reason,
        public array $lines,
    ) {}
}
