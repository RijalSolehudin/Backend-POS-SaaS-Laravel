<?php

declare(strict_types=1);

namespace App\Modules\Sync\Domain\Models;

use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $outlet_id
 * @property string $offline_order_draft_id
 * @property string $event_type
 * @property int $sequence_number
 * @property array<string, mixed>|null $payload
 */
final class OfflineOrderEvent extends Model
{
    use HasLowercaseUlids;

    protected $table = 'offline_order_events';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'sequence_number' => 'integer',
            'payload' => 'array',
        ];
    }
}
