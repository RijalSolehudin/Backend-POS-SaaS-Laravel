<?php

declare(strict_types=1);

namespace App\Modules\Dining\Application\Data;

final readonly class OpenTableSessionInput
{
    public function __construct(
        public string $outletId,
        public string $tableId,
        public int $partySize,
        public ?string $notes = null,
    ) {}
}
