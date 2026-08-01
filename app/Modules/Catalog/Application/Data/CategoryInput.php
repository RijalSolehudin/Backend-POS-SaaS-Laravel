<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Data;

final readonly class CategoryInput
{
    public function __construct(public string $name) {}
}
