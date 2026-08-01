<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Data;

final readonly class OutletCatalogSummary
{
    public function __construct(
        public string $outletId,
        public string $name,
    ) {}
}
