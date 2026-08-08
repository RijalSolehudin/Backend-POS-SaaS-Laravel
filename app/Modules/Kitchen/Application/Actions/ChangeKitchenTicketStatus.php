<?php

declare(strict_types=1);

namespace App\Modules\Kitchen\Application\Actions;

use App\Modules\Kitchen\Application\Exceptions\KitchenException;
use App\Modules\Kitchen\Domain\Enums\KitchenTicketStatus;
use App\Modules\Kitchen\Domain\Events\KitchenTicketChanged;
use App\Modules\Kitchen\Domain\Models\KitchenTicket;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use Carbon\CarbonImmutable;

final readonly class ChangeKitchenTicketStatus
{
    public function __construct(
        private TenantPermissionGuard $permissions,
        private RecordKitchenTicketEvent $events,
    ) {}

    public function handle(TenantRequestContext $context, string $outletId, string $ticketId, KitchenTicketStatus $status): KitchenTicket
    {
        $this->permissions->authorizeOperatePos($context, $outletId);
        $ticket = KitchenTicket::query()
            ->where('tenant_id', $context->tenantId)
            ->where('outlet_id', $outletId)
            ->whereKey($ticketId)
            ->first();

        if (! $ticket instanceof KitchenTicket) {
            throw KitchenException::ticketNotFound();
        }

        if (! $this->canTransition($ticket->status, $status)) {
            throw KitchenException::ticketInvalidState();
        }

        if ($ticket->status === $status) {
            return $ticket;
        }

        $ticket->forceFill([
            'status' => $status,
            'last_actor_user_id' => $context->userId,
            'last_state_changed_at' => CarbonImmutable::now(),
        ])->save();

        $this->events->handle($ticket, 'ticket.'.$status->value, $context->userId);
        event(KitchenTicketChanged::fromTicket($ticket, 'ticket.'.$status->value));

        return $ticket;
    }

    private function canTransition(KitchenTicketStatus $current, KitchenTicketStatus $next): bool
    {
        return match ($current) {
            KitchenTicketStatus::Queued => in_array($next, [KitchenTicketStatus::Preparing, KitchenTicketStatus::Cancelled], true),
            KitchenTicketStatus::Preparing => in_array($next, [KitchenTicketStatus::Ready, KitchenTicketStatus::Cancelled], true),
            KitchenTicketStatus::Ready => in_array($next, [KitchenTicketStatus::Served, KitchenTicketStatus::Cancelled], true),
            KitchenTicketStatus::Served, KitchenTicketStatus::Cancelled => false,
        };
    }
}
