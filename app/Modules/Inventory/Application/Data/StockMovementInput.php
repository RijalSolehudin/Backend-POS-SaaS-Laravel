<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Data;

use App\Modules\Inventory\Domain\Enums\StockMovementType;

final readonly class StockMovementInput
{
    public function __construct(
        public string $tenantId,
        public string $outletId,
        public string $itemId,
        public string $unitId,
        public ?string $actorUserId,
        public StockMovementType $movementType,
        public string $sourceType,
        public ?string $sourceId,
        public string $quantity,
        public ?int $unitCostMinor,
        public ?int $totalCostMinor,
        public string $currency,
        public ?string $reason,
        public string $idempotencyKey,
    ) {}
}
