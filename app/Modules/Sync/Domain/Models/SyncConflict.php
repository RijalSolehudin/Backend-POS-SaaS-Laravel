<?php

declare(strict_types=1);

namespace App\Modules\Sync\Domain\Models;

use App\Modules\Sync\Domain\Enums\SyncConflictStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $outlet_id
 * @property string|null $device_id
 * @property string|null $sync_inbox_record_id
 * @property string $conflict_type
 * @property SyncConflictStatus $status
 * @property array<string, mixed>|null $payload
 * @property string|null $resolved_by
 * @property string|null $resolution_reason
 * @property CarbonImmutable|null $resolved_at
 */
final class SyncConflict extends Model
{
    use HasLowercaseUlids;

    protected $table = 'sync_conflicts';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => SyncConflictStatus::class,
            'payload' => 'array',
            'resolved_at' => 'immutable_datetime',
        ];
    }
}
