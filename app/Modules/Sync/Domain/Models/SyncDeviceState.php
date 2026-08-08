<?php

declare(strict_types=1);

namespace App\Modules\Sync\Domain\Models;

use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $outlet_id
 * @property string $device_id
 * @property int $last_accepted_sequence
 * @property string|null $last_outbox_cursor
 * @property CarbonImmutable|null $last_synced_at
 * @property CarbonImmutable|null $revoked_at
 */
final class SyncDeviceState extends Model
{
    use HasLowercaseUlids;

    protected $table = 'sync_device_states';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'last_accepted_sequence' => 'integer',
            'last_synced_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ];
    }
}
