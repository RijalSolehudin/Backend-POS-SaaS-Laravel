<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Data;

final readonly class DisableTenantResult
{
    public function __construct(
        public string $tenantId,
        public bool $wasChanged,
    ) {}
}
