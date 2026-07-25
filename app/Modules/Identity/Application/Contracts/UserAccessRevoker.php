<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Contracts;

interface UserAccessRevoker
{
    public function revokeAll(string $userId): void;
}
