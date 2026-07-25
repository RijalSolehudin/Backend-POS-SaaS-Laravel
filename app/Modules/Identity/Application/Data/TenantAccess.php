<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Data;

final readonly class TenantAccess
{
    public function __construct(
        public string $tenantId,
        public bool $tenantActive,
    ) {}
}
