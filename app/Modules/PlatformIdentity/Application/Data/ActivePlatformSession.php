<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Application\Data;

use DateTimeImmutable;

final readonly class ActivePlatformSession
{
    public function __construct(
        public string $id,
        public ?string $ipAddress,
        public ?string $userAgent,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $lastActivityAt,
    ) {}
}
