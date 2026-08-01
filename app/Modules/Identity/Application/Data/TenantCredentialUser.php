<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Data;

final readonly class TenantCredentialUser
{
    public function __construct(
        public string $userId,
        public string $tenantId,
        public bool $mustChangePassword,
    ) {}
}
