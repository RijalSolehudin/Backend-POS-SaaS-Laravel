<?php

declare(strict_types=1);

namespace App\Modules\Recipe\Application\Data;

final readonly class RecipeVersionInput
{
    /**
     * @param  list<RecipeIngredientInput>  $ingredients
     */
    public function __construct(
        public string $recipeId,
        public string $yieldQuantity,
        public int $yieldPercent,
        public string $currency,
        public array $ingredients,
    ) {}
}
