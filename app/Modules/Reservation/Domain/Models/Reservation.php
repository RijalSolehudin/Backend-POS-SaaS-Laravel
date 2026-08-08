<?php

declare(strict_types=1);

namespace App\Modules\Reservation\Domain\Models;

use App\Modules\Reservation\Domain\Enums\ReservationStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $outlet_id
 * @property string|null $table_id
 * @property string|null $table_session_id
 * @property int $party_size
 * @property ReservationStatus $status
 */
final class Reservation extends Model
{
    use HasLowercaseUlids;

    protected $table = 'reservations';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'party_size' => 'integer',
            'status' => ReservationStatus::class,
            'reserved_at' => 'immutable_datetime',
            'seated_at' => 'immutable_datetime',
        ];
    }
}
