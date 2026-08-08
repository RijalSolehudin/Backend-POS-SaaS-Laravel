<?php

declare(strict_types=1);

namespace App\Modules\Dining\Application\Data;

final readonly class DiningTableInput
{
    public function __construct(
        public string $outletId,
        public string $floorId,
        public string $name,
        public string $code,
        public int $capacity,
        public int $displayOrder,
    ) {}
}
