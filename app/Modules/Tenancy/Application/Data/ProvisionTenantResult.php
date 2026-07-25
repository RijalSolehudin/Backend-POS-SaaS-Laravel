<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Data;

final readonly class ProvisionTenantResult
{
    public function __construct(
        public string $tenantId,
        public string $outletId,
        public string $ownerUserId,
        public string $membershipId,
        public string $roleAssignmentId,
        public string $ownerEmail,
        public bool $wasReplayed,
    ) {}
}
