<?php

declare(strict_types=1);

namespace App\Modules\Dining\Domain\Models;

use App\Modules\Dining\Domain\Enums\TableSessionStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $outlet_id
 * @property string $table_id
 * @property string|null $previous_table_id
 * @property string|null $target_session_id
 * @property string|null $open_table_key
 * @property int $party_size
 * @property TableSessionStatus $status
 * @property string $opened_by
 * @property CarbonImmutable $opened_at
 * @property string|null $closed_by
 * @property CarbonImmutable|null $closed_at
 * @property string|null $cancelled_by
 * @property CarbonImmutable|null $cancelled_at
 * @property CarbonImmutable|null $transferred_at
 * @property CarbonImmutable|null $merged_at
 * @property string|null $cancel_reason
 * @property string|null $notes
 */
final class DiningTableSession extends Model
{
    use HasLowercaseUlids;

    protected $table = 'dining_table_sessions';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'party_size' => 'integer',
            'status' => TableSessionStatus::class,
            'opened_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
            'transferred_at' => 'immutable_datetime',
            'merged_at' => 'immutable_datetime',
        ];
    }
}
