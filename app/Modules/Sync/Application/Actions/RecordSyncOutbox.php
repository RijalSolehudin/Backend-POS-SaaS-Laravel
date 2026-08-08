<?php

declare(strict_types=1);

namespace App\Modules\Sync\Application\Actions;

use App\Modules\Sync\Domain\Models\SyncOutboxRecord;

final readonly class RecordSyncOutbox
{
    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function handle(
        string $tenantId,
        string $outletId,
        string $eventType,
        ?string $resourceType = null,
        ?string $resourceId = null,
        ?array $payload = null,
    ): SyncOutboxRecord {
        return SyncOutboxRecord::query()->create([
            'tenant_id' => $tenantId,
            'outlet_id' => $outletId,
            'event_type' => $eventType,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'payload' => $payload,
        ]);
    }
}
