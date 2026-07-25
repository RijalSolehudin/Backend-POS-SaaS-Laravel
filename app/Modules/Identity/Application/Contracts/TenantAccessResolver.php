<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Contracts;

use App\Modules\Identity\Application\Data\TenantAccess;

interface TenantAccessResolver
{
    public function forUser(string $userId): ?TenantAccess;
}
