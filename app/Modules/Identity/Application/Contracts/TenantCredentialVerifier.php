<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Contracts;

use App\Modules\Identity\Application\Data\TenantCredentialUser;

interface TenantCredentialVerifier
{
    public function verify(string $email, string $password): ?TenantCredentialUser;
}
