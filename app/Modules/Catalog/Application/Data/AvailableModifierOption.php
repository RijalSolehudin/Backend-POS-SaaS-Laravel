<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Data;

final readonly class AvailableModifierOption
{
    public function __construct(
        public string $id,
        public string $name,
        public int $priceDeltaMinor,
        public string $currency,
    ) {}
}
