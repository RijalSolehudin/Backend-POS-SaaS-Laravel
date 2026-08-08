<?php

declare(strict_types=1);

namespace App\Modules\OrderingChannel\Application\Actions;

use App\Modules\Dining\Application\Actions\LinkOrderToTableSession;
use App\Modules\Sales\Application\Actions\AddOrderItem;
use App\Modules\Sales\Application\Actions\CreateDraftOrder;
use App\Modules\Sales\Application\Data\OrderItemSelection;
use App\Modules\Sales\Domain\Models\Order;
use App\Modules\Tenancy\Application\Data\PosOutletContext;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;

final readonly class CreateWaiterOrder
{
    public function __construct(
        private TenantPermissionGuard $permissions,
        private CreateDraftOrder $createOrder,
        private AddOrderItem $addItem,
        private LinkOrderToTableSession $linkTableSession,
    ) {}

    /**
     * @param  list<OrderItemSelection>  $items
     */
    public function handle(TenantRequestContext $context, string $outletId, array $items, ?string $tableSessionId, string $idempotencyKey): Order
    {
        $this->permissions->authorizeOperatePos($context, $outletId);

        $posContext = new PosOutletContext($context->tenantId, $outletId, 'waiter-workflow', $context->userId);
        $order = $this->createOrder->handle($posContext, 'waiter-'.$idempotencyKey);

        foreach ($items as $item) {
            $order = $this->addItem->handle($posContext, $order->id, $item);
        }

        if ($tableSessionId !== null) {
            $this->linkTableSession->handle($context, $tableSessionId, $order->id);
        }

        return $order->refresh();
    }
}
