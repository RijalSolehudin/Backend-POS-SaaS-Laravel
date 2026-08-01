<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Application\Data;

final readonly class PurchaseOrderInput
{
    /**
     * @param  list<PurchaseOrderLineInput>  $lines
     */
    public function __construct(
        public string $outletId,
        public string $supplierId,
        public string $currency,
        public array $lines,
        public ?string $notes = null,
    ) {}
}
