<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Data;

use Carbon\CarbonImmutable;

final readonly class IssuedPosSession
{
    public function __construct(
        public string $token,
        public CarbonImmutable $expiresAt,
        public string $tenantId,
        public string $outletId,
        public string $deviceId,
        public string $userId,
        public bool $mustChangePassword,
    ) {}
}
