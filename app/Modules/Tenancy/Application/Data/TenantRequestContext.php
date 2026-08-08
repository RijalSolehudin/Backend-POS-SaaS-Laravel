<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Data;

use App\Modules\Tenancy\Domain\Enums\MembershipType;

final readonly class TenantRequestContext
{
    public function __construct(            
        public string $tenantId,
        public string $userId,
        public MembershipType $membershipType,
    ) {}

    public function isOwner(): bool
    {
        return $this->membershipType === MembershipType::Owner;
    }
}
