<?php

declare(strict_types=1);

namespace App\Modules\Kitchen\Application\Actions;

use App\Modules\Kitchen\Domain\Models\KitchenTicket;
use App\Modules\Kitchen\Domain\Models\KitchenTicketEvent;
use Carbon\CarbonImmutable;

final readonly class RecordKitchenTicketEvent
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function handle(KitchenTicket $ticket, string $eventType, ?string $actorUserId, ?array $metadata = null): KitchenTicketEvent
    {
        return KitchenTicketEvent::query()->create([
            'tenant_id' => $ticket->tenant_id,
            'outlet_id' => $ticket->outlet_id,
            'ticket_id' => $ticket->id,
            'event_type' => $eventType,
            'actor_user_id' => $actorUserId,
            'metadata' => $metadata,
            'occurred_at' => CarbonImmutable::now(),
        ]);
    }
}
