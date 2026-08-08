<?php

declare(strict_types=1);

namespace App\Modules\Sync\Domain\Models;

use App\Modules\Sync\Domain\Enums\SyncRecordStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $outlet_id
 * @property string $device_id
 * @property string $client_record_id
 * @property string $action
 * @property int $sequence_number
 * @property string $idempotency_key
 * @property string $payload_hash
 * @property SyncRecordStatus $status
 * @property string|null $resource_type
 * @property string|null $resource_id
 * @property array<string, mixed>|null $payload
 * @property array<string, mixed>|null $response
 */
final class SyncInboxRecord extends Model
{
    use HasLowercaseUlids;

    protected $table = 'sync_inbox_records';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'sequence_number' => 'integer',
            'status' => SyncRecordStatus::class,
            'payload' => 'array',
            'response' => 'array',
        ];
    }
}
