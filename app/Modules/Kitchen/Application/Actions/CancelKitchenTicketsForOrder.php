<?php

declare(strict_types=1);

namespace App\Modules\Kitchen\Application\Actions;

use App\Modules\Kitchen\Domain\Enums\KitchenTicketStatus;
use App\Modules\Kitchen\Domain\Events\KitchenTicketChanged;
use App\Modules\Kitchen\Domain\Models\KitchenTicket;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use Carbon\CarbonImmutable;

final readonly class CancelKitchenTicketsForOrder
{
    public function __construct(private RecordKitchenTicketEvent $events) {}

    public function handle(TenantRequestContext $context, string $outletId, string $orderId, string $reason): int
    {
        $tickets = KitchenTicket::query()
            ->where('tenant_id', $context->tenantId)
            ->where('outlet_id', $outletId)
            ->where('order_id', $orderId)
            ->whereNotIn('status', [KitchenTicketStatus::Served->value, KitchenTicketStatus::Cancelled->value])
            ->get();

        foreach ($tickets as $ticket) {
            $ticket->forceFill([
                'status' => KitchenTicketStatus::Cancelled,
                'last_actor_user_id' => $context->userId,
                'last_state_changed_at' => CarbonImmutable::now(),
            ])->save();
            $this->events->handle($ticket, 'ticket.cancelled', $context->userId, ['reason' => $reason]);
            event(KitchenTicketChanged::fromTicket($ticket, 'ticket.cancelled'));
        }

        return $tickets->count();
    }
}
