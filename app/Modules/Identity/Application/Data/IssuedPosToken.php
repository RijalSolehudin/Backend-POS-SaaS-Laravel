<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Data;

use Carbon\CarbonImmutable;

final readonly class IssuedPosToken
{
    public function __construct(
        public string $plainTextToken,
        public CarbonImmutable $expiresAt,
    ) {}
}
