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
 * @property string $event_type
 * @property string|null $resource_type
 * @property string|null $resource_id
 * @property array<string, mixed>|null $payload
 * @property CarbonImmutable|null $created_at
 */
final class SyncOutboxRecord extends Model
{
    use HasLowercaseUlids;

    protected $table = 'sync_outbox_records';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}
