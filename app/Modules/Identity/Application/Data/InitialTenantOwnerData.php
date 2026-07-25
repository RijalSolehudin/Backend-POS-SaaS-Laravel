<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Data;

final readonly class InitialTenantOwnerData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}
}
