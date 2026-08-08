<?php

declare(strict_types=1);

namespace App\Modules\Reservation\Application\Data;

use Carbon\CarbonImmutable;

final readonly class ReservationInput
{
    public function __construct(
        public string $outletId,
        public CarbonImmutable $reservedAt,
        public int $partySize,
        public ?string $tableId = null,
        public ?string $customerName = null,
        public ?string $customerPhone = null,
        public ?string $notes = null,
    ) {}
}
