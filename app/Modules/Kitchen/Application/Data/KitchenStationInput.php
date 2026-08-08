<?php

declare(strict_types=1);

namespace App\Modules\Kitchen\Application\Data;

final readonly class KitchenStationInput
{
    public function __construct(
        public string $outletId,
        public string $name,
        public string $code,
        public bool $isFallback,
    ) {}
}
