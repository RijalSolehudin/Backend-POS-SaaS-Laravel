<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Data;

final readonly class AvailableCatalogVariant
{
    public function __construct(
        public string $id,
        public string $sku,
        public string $name,
        public int $priceMinor,
        public string $currency,
        public bool $isDefault,
        /** @var list<AvailableModifierGroup> */
        public array $modifierGroups = [],
    ) {}
}
