<?php

declare(strict_types=1);

namespace App\Modules\Sync\Application\Data;

final readonly class SyncMutationResult
{
    /**
     * @param  array<string, mixed>|null  $response
     */
    public function __construct(
        public string $status,
        public ?string $resourceType,
        public ?string $resourceId,
        public ?array $response,
    ) {}
}
