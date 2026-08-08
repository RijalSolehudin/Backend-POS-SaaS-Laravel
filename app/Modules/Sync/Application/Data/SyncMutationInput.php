<?php

declare(strict_types=1);

namespace App\Modules\Sync\Application\Data;

final readonly class SyncMutationInput
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $tenantId,
        public string $outletId,
        public string $deviceId,
        public string $clientRecordId,
        public string $action,
        public int $sequenceNumber,
        public string $idempotencyKey,
        public string $payloadHash,
        public array $payload,
    ) {}
}
