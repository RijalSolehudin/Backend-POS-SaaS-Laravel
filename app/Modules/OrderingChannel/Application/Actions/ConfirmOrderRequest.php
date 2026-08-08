<?php

declare(strict_types=1);

namespace App\Modules\OrderingChannel\Application\Actions;

use App\Modules\Dining\Application\Actions\LinkOrderToTableSession;
use App\Modules\OrderingChannel\Application\Exceptions\OrderingChannelException;
use App\Modules\OrderingChannel\Domain\Enums\OrderRequestStatus;
use App\Modules\OrderingChannel\Domain\Models\OrderingCustomerCartItem;
use App\Modules\OrderingChannel\Domain\Models\OrderingOrderRequest;
use App\Modules\Sales\Application\Actions\AddOrderItem;
use App\Modules\Sales\Application\Actions\CreateDraftOrder;
use App\Modules\Sales\Application\Data\OrderItemSelection;
use App\Modules\Sales\Domain\Models\Order;
use App\Modules\Tenancy\Application\Data\PosOutletContext;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class ConfirmOrderRequest
{
    public function __construct(
        private TenantPermissionGuard $permissions,
        private CreateDraftOrder $createOrder,
        private AddOrderItem $addItem,
        private LinkOrderToTableSession $linkTableSession,
    ) {}

    public function handle(TenantRequestContext $context, string $requestId, string $idempotencyKey): Order
    {
        return DB::transaction(function () use ($context, $requestId, $idempotencyKey): Order {
            $request = OrderingOrderRequest::query()
                ->where('tenant_id', $context->tenantId)
                ->whereKey($requestId)
                ->lockForUpdate()
                ->first();

            if (! $request instanceof OrderingOrderRequest) {
                throw OrderingChannelException::orderRequestNotFound();
            }

            $this->permissions->authorizeOperatePos($context, $request->outlet_id);

            if ($request->status === OrderRequestStatus::Confirmed && $request->sales_order_id !== null) {
                $order = Order::query()->whereKey($request->sales_order_id)->first();

                if ($order instanceof Order) {
                    return $order;
                }
            }

            if ($request->status !== OrderRequestStatus::Pending || $request->expires_at->lessThanOrEqualTo(CarbonImmutable::now())) {
                throw OrderingChannelException::orderRequestInvalidState();
            }

            $posContext = new PosOutletContext($context->tenantId, $request->outlet_id, 'ordering-channel', $context->userId);
            $order = $this->createOrder->handle($posContext, 'order-request-'.$request->id.'-'.$idempotencyKey);
            $items = OrderingCustomerCartItem::query()
                ->where('tenant_id', $context->tenantId)
                ->where('cart_id', $request->cart_id)
                ->get();

            foreach ($items as $item) {
                $this->addItem->handle($posContext, $order->id, new OrderItemSelection(
                    productId: $item->product_id,
                    quantity: $item->quantity,
                    variantId: $item->variant_id,
                    modifierOptionIds: $item->modifier_option_ids ?? [],
                ));
            }

            if ($request->table_session_id !== null) {
                $this->linkTableSession->handle($context, $request->table_session_id, $order->id);
            }

            $request->forceFill([
                'status' => OrderRequestStatus::Confirmed,
                'idempotency_key' => $idempotencyKey,
                'sales_order_id' => $order->id,
                'confirmed_by' => $context->userId,
                'confirmed_at' => CarbonImmutable::now(),
            ])->save();

            return $order->refresh();
        });
    }
}
