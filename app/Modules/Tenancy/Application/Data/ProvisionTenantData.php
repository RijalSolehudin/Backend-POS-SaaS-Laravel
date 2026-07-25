<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Data;

final readonly class ProvisionTenantData
{
    public function __construct(
        public string $idempotencyKey,
        public string $tenantName,
        public string $tenantCode,
        public string $outletName,
        public string $outletCode,
        public string $ownerName,
        public string $ownerEmail,
        public string $ownerPassword,
        public string $currency,
        public string $timezone,
        public string $reason,
    ) {}
}
