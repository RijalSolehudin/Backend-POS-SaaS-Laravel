<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Data;

final readonly class OrderItemSelection
{
    /**
     * @param  list<string>  $modifierOptionIds
     */
    public function __construct(
        public string $productId,
        public string $quantity,
        public ?string $variantId = null,
        public array $modifierOptionIds = [],
    ) {}
}
