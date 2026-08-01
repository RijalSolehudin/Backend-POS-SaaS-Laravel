<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Contracts;

interface SensitiveActionApprovalAuthority
{
    public function canApproveForOutlet(string $tenantId, string $outletId, string $userId): bool;
}
