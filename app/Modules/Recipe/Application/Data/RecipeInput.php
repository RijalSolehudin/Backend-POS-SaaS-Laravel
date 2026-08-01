<?php

declare(strict_types=1);

namespace App\Modules\Recipe\Application\Data;

final readonly class RecipeInput
{
    public function __construct(
        public string $name,
        public string $sku,
        public bool $requiresRecipe,
    ) {}
}
