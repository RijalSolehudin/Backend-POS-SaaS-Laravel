<?php

declare(strict_types=1);

namespace App\Modules\Kitchen\Application\Actions;

use App\Modules\Kitchen\Application\Data\KitchenTicketCreationResult;
use App\Modules\Kitchen\Application\Exceptions\KitchenException;
use App\Modules\Kitchen\Domain\Enums\KitchenTicketStatus;
use App\Modules\Kitchen\Domain\Events\KitchenTicketChanged;
use App\Modules\Kitchen\Domain\Models\KitchenTicket;
use App\Modules\Kitchen\Domain\Models\KitchenTicketEvent;
use App\Modules\Kitchen\Domain\Models\KitchenTicketItem;
use App\Modules\Sales\Application\Contracts\KitchenOrderReference;
use App\Modules\Sales\Application\Data\KitchenOrderItemSummary;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class CreateKitchenTicketsForOrder
{
    public function __construct(
        private TenantPermissionGuard $permissions,
        private KitchenOrderReference $orders,
        private ResolveKitchenRouting $routing,
        private RecordKitchenTicketEvent $events,
    ) {}

    public function handle(TenantRequestContext $context, string $outletId, string $orderId): KitchenTicketCreationResult
    {
        $this->permissions->authorizeOperatePos($context, $outletId);
        $order = $this->orders->orderWithItems($context->tenantId, $outletId, $orderId);

        if ($order === null) {
            throw KitchenException::orderNotFound();
        }

        return DB::transaction(function () use ($context, $outletId, $order): KitchenTicketCreationResult {
            $tickets = [];
            $missing = [];

            foreach ($order->items as $item) {
                $routing = $this->routing->handle($context, $outletId, $item->productId, $item->variantId, $item->categoryId);

                if ($routing->stationId === null) {
                    $missing[] = $item->itemId;

                    continue;
                }

                $ticket = KitchenTicket::query()->firstOrCreate(
                    [
                        'tenant_id' => $context->tenantId,
                        'outlet_id' => $outletId,
                        'order_id' => $order->orderId,
                        'station_id' => $routing->stationId,
                    ],
                    [
                        'order_number' => $order->orderNumber,
                        'status' => KitchenTicketStatus::Queued,
                        'last_actor_user_id' => $context->userId,
                        'last_state_changed_at' => CarbonImmutable::now(),
                    ],
                );

                $this->ensureTicketItem($ticket, $item);

                if (! $this->hasEvent($ticket, 'ticket.created')) {
                    $this->events->handle($ticket, 'ticket.created', $context->userId, ['order_id' => $order->orderId]);
                    event(KitchenTicketChanged::fromTicket($ticket, 'ticket.created'));
                }

                $tickets[$ticket->id] = $ticket;
            }

            return new KitchenTicketCreationResult(array_values($tickets), $missing);
        });
    }

    private function ensureTicketItem(KitchenTicket $ticket, KitchenOrderItemSummary $item): void
    {
        KitchenTicketItem::query()->firstOrCreate(
            [
                'tenant_id' => $ticket->tenant_id,
                'outlet_id' => $ticket->outlet_id,
                'ticket_id' => $ticket->id,
                'order_item_id' => $item->itemId,
            ],
            [
                'product_id' => $item->productId,
                'variant_id' => $item->variantId,
                'product_name' => $item->productName,
                'variant_name' => $item->variantName,
                'quantity' => $item->quantity,
            ],
        );
    }

    private function hasEvent(KitchenTicket $ticket, string $eventType): bool
    {
        return KitchenTicketEvent::query()
            ->where('tenant_id', $ticket->tenant_id)
            ->where('ticket_id', $ticket->id)
            ->where('event_type', $eventType)
            ->exists();
    }
}
