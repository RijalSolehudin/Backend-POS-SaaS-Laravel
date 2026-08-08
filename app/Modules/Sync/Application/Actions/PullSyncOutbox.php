<?php

declare(strict_types=1);

namespace App\Modules\Sync\Application\Actions;

use App\Modules\Sync\Domain\Models\SyncDeviceState;
use App\Modules\Sync\Domain\Models\SyncOutboxRecord;
use App\Modules\Tenancy\Application\Data\PosOutletContext;
use Carbon\CarbonImmutable;

final readonly class PullSyncOutbox
{
    /**
     * @return list<SyncOutboxRecord>
     */
    public function handle(PosOutletContext $context, ?string $afterCursor = null, int $limit = 100): array
    {
        $query = SyncOutboxRecord::query()
            ->where('tenant_id', $context->tenantId)
            ->where('outlet_id', $context->outletId)
            ->orderBy('id')
            ->limit(max(1, min(500, $limit)));

        if ($afterCursor !== null) {
            $query->where('id', '>', $afterCursor);
        }

        $records = array_values($query->get()->all());
        $last = end($records);

        if ($last instanceof SyncOutboxRecord) {
            SyncDeviceState::query()->updateOrCreate(
                [
                    'tenant_id' => $context->tenantId,
                    'outlet_id' => $context->outletId,
                    'device_id' => $context->deviceId,
                ],
                [
                    'last_outbox_cursor' => $last->id,
                    'last_synced_at' => CarbonImmutable::now(),
                ],
            );
        }

        return $records;
    }
}
