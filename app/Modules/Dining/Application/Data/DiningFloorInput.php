<?php

namespace App\Modules\Dining\Application\Data;

final class DiningFloorInput
{
    public function __construct(
        public readonly string $tenantId,
        public readonly string $outletId,
        public readonly string $name,
        public readonly string $status,
        public readonly int $displayOrder = 0,
    ) {
    }
}


