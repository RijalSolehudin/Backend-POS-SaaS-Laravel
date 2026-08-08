<?php
declare(strict_types=1);

namespace App\Modules\Dining\Application\Data;

final class DiningTableInput
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public readonly string $tenantId,
        public readonly string $outletId,
        public readonly string $diningFloorId,
        public readonly string $code,
        public readonly string $name,
        public readonly string $status,
        public readonly int $seats = 2,
        public readonly int $displayOrder = 0,
    ) {
    }
}

