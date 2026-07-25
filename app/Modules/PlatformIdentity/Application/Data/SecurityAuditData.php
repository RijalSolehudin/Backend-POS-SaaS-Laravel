<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Application\Data;

final readonly class SecurityAuditData
{
    /**
     * @param  array<string, bool|int|string|null>  $metadata
     */
    public function __construct(
        public string $eventType,
        public string $outcome,
        public string $correlationId,
        public ?string $actorType = null,
        public ?string $actorId = null,
        public ?string $subjectType = null,
        public ?string $subjectId = null,
        public ?string $requestId = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
        public ?string $sessionIdHash = null,
        public ?string $reason = null,
        public array $metadata = [],
        public bool $sendAlert = false,
    ) {}
}
