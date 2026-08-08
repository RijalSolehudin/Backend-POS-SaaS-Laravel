<?php

declare(strict_types=1);

namespace App\Modules\Kitchen\Domain\Events;

use App\Modules\Kitchen\Domain\Models\KitchenTicket;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

final readonly class KitchenTicketChanged implements ShouldBroadcastNow
{
    public function __construct(
        public string $tenantId,
        public string $outletId,
        public string $ticketId,
        public string $stationId,
        public string $status,
        public string $eventType,
    ) {}

    public static function fromTicket(KitchenTicket $ticket, string $eventType): self
    {
        return new self(
            tenantId: $ticket->tenant_id,
            outletId: $ticket->outlet_id,
            ticketId: $ticket->id,
            stationId: $ticket->station_id,
            status: $ticket->status->value,
            eventType: $eventType,
        );
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("tenant.{$this->tenantId}.outlet.{$this->outletId}.kds");
    }

    /**
     * @return array<string, string>
     */
    public function broadcastWith(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'outlet_id' => $this->outletId,
            'ticket_id' => $this->ticketId,
            'station_id' => $this->stationId,
            'status' => $this->status,
            'event_type' => $this->eventType,
        ];
    }
}
