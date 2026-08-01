<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Data;

final readonly class TenantCatalogSummary
{
    public function __construct(
        public string $tenantId,
        public string $name,
        public string $currency,
    ) {}
}
