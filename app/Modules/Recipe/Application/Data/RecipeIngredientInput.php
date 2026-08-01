<?php

declare(strict_types=1);

namespace App\Modules\Recipe\Application\Data;

final readonly class RecipeIngredientInput
{
    public function __construct(
        public string $inventoryItemId,
        public string $quantity,
    ) {}
}
