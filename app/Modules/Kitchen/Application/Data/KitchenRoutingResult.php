<?php

declare(strict_types=1);

namespace App\Modules\Kitchen\Application\Data;

final readonly class KitchenRoutingResult
{
    public function __construct(
        public ?string $stationId,
        public bool $fallbackUsed,
        public bool $missingRouting,
    ) {}
}
