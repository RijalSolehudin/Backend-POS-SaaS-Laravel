<?php

declare(strict_types=1);

namespace App\Modules\Dining\Application\Data;

final readonly class DiningFloorInput
{
    public function __construct(
        public string $outletId,
        public string $name,
        public string $code,
        public int $displayOrder,
    ) {}
}
