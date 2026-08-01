<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Data;

final readonly class PosTokenRequest
{
    public function __construct(
        public string $email,
        public string $password,
        public string $installationId,
        public string $outletId,
    ) {}
}
