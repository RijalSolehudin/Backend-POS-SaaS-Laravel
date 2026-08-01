<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Data;

final readonly class PosOutletContext
{
    public function __construct(
        public string $tenantId,
        public string $outletId,
        public string $deviceId,
        public string $userId,
    ) {}
}
