<?php

declare(strict_types=1);

namespace App\Modules\Kitchen\Domain\Models;

use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $outlet_id
 * @property string $ticket_id
 * @property string $event_type
 * @property string|null $actor_user_id
 * @property CarbonImmutable $occurred_at
 * @property array<string, mixed>|null $metadata
 */
final class KitchenTicketEvent extends Model
{
    use HasLowercaseUlids;

    protected $table = 'kitchen_ticket_events';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'immutable_datetime',
            'metadata' => 'array',
        ];
    }
}
