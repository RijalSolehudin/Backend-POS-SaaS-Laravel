<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Data;

final readonly class TenancyAuditData
{
    /**
     * @param  array<string, bool|int|string|null>  $metadata
     */
    public function __construct(
        public string $eventType,
        public string $outcome,
        public string $actorType,
        public string $actorId,
        public string $correlationId,
        public ?string $targetTenantId = null,
        public ?string $reason = null,
        public array $metadata = [],
    ) {}
}
