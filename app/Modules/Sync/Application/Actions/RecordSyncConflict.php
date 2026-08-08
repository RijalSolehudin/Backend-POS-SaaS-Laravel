<?php

declare(strict_types=1);

namespace App\Modules\Sync\Application\Actions;

use App\Modules\Sync\Domain\Enums\SyncConflictStatus;
use App\Modules\Sync\Domain\Models\SyncConflict;

final readonly class RecordSyncConflict
{
    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function handle(
        string $tenantId,
        string $outletId,
        string $conflictType,
        ?string $deviceId = null,
        ?string $inboxRecordId = null,
        ?array $payload = null,
    ): SyncConflict {
        return SyncConflict::query()->create([
            'tenant_id' => $tenantId,
            'outlet_id' => $outletId,
            'device_id' => $deviceId,
            'sync_inbox_record_id' => $inboxRecordId,
            'conflict_type' => $conflictType,
            'status' => SyncConflictStatus::Open,
            'payload' => $payload,
        ]);
    }
}
