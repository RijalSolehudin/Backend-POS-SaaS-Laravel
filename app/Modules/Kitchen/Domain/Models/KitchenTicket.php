<?php

declare(strict_types=1);

namespace App\Modules\Kitchen\Domain\Models;

use App\Modules\Kitchen\Domain\Enums\KitchenTicketStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $outlet_id
 * @property string $station_id
 * @property string $order_id
 * @property string $order_number
 * @property KitchenTicketStatus $status
 * @property string|null $last_actor_user_id
 * @property CarbonImmutable|null $last_state_changed_at
 */
final class KitchenTicket extends Model
{
    use HasLowercaseUlids;

    protected $table = 'kitchen_tickets';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => KitchenTicketStatus::class,
            'last_state_changed_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return HasMany<KitchenTicketItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(KitchenTicketItem::class, 'ticket_id');
    }
}
