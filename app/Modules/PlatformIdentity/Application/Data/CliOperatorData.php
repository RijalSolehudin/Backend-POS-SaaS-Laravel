<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Application\Data;

final readonly class CliOperatorData
{
    public function __construct(
        public string $identity,
        public string $reason,
        public ?string $reference,
        public string $osUser,
        public string $hostname,
    ) {}
}
