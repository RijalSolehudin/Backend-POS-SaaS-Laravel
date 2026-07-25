<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Data;

final readonly class TenantUserSummary
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public bool $active,
    ) {}
}
