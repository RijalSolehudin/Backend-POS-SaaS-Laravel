<?php

declare(strict_types=1);

namespace App\Modules\OrderingChannel\Application\Data;

final readonly class CustomerCartItemInput
{
    /**
     * @param  list<string>  $modifierOptionIds
     */
    public function __construct(
        public string $productId,
        public string $quantity,
        public ?string $variantId = null,
        public array $modifierOptionIds = [],
        public ?string $notes = null,
    ) {}
}
