<?php

declare(strict_types=1);

namespace App\Modules\Kitchen\Application\Actions;

use App\Modules\Kitchen\Domain\Enums\KitchenTicketStatus;
use App\Modules\Kitchen\Domain\Models\KitchenTicket;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;

final readonly class GetKdsSnapshot
{
    public function __construct(private TenantPermissionGuard $permissions) {}

    /**
     * @return array{tickets: list<array<string, mixed>>}
     */
    public function handle(TenantRequestContext $context, string $outletId, ?string $stationId = null): array
    {
        $this->permissions->authorizeOperatePos($context, $outletId);

        $query = KitchenTicket::query()
            ->with('items')
            ->where('tenant_id', $context->tenantId)
            ->where('outlet_id', $outletId)
            ->whereIn('status', [
                KitchenTicketStatus::Queued->value,
                KitchenTicketStatus::Preparing->value,
                KitchenTicketStatus::Ready->value,
            ])
            ->orderBy('created_at');

        if ($stationId !== null) {
            $query->where('station_id', $stationId);
        }

        $tickets = [];

        foreach ($query->get() as $ticket) {
            $items = [];

            foreach ($ticket->items as $item) {
                $items[] = [
                    'id' => $item->id,
                    'order_item_id' => $item->order_item_id,
                    'product_name' => $item->product_name,
                    'variant_name' => $item->variant_name,
                    'quantity' => $item->quantity,
                ];
            }

            $tickets[] = [
                'id' => $ticket->id,
                'tenant_id' => $ticket->tenant_id,
                'outlet_id' => $ticket->outlet_id,
                'station_id' => $ticket->station_id,
                'order_id' => $ticket->order_id,
                'order_number' => $ticket->order_number,
                'status' => $ticket->status->value,
                'last_state_changed_at' => $ticket->last_state_changed_at?->toJSON(),
                'items' => $items,
            ];
        }

        return ['tickets' => $tickets];
    }
}
