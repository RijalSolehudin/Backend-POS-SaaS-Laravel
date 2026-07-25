<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Data;

final readonly class InitialTenantOwnerResult
{
    public function __construct(
        public string $userId,
        public string $roleAssignmentId,
        public string $normalizedEmail,
    ) {}
}
