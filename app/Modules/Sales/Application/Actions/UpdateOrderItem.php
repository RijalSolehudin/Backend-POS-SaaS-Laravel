<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Actions;

use App\Modules\Sales\Application\Exceptions\OrderException;
use App\Modules\Sales\Application\Services\QuantityCalculator;
use App\Modules\Sales\Domain\Enums\OrderStatus;
use App\Modules\Sales\Domain\Models\Order;
use App\Modules\Sales\Domain\Models\OrderItem;
use App\Modules\Tenancy\Application\Data\PosOutletContext;
use Illuminate\Support\Facades\DB;

final readonly class UpdateOrderItem
{
    public function __construct(private QuantityCalculator $calculator) {}

    public function handle(PosOutletContext $context, string $orderId, string $itemId, string $quantity): Order
    {
        return DB::transaction(function () use ($context, $orderId, $itemId, $quantity): Order {
            $order = $this->draftOrder($context, $orderId);
            $item = OrderItem::query()
                ->where('tenant_id', $context->tenantId)
                ->where('order_id', $order->id)
                ->whereKey($itemId)
                ->lockForUpdate()
                ->first();

            if (! $item instanceof OrderItem) {
                throw OrderException::itemNotFound();
            }

            $item->forceFill([
                'quantity' => $quantity,
                'line_subtotal_minor' => $this->calculator->lineSubtotalMinor($item->unit_price_minor, $quantity),
            ])->save();

            return $this->recalculate($order);
        });
    }

    private function draftOrder(PosOutletContext $context, string $orderId): Order
    {
        $order = Order::query()
            ->where('tenant_id', $context->tenantId)
            ->where('outlet_id', $context->outletId)
            ->where('user_id', $context->userId)
            ->whereKey($orderId)
            ->lockForUpdate()
            ->first();

        if (! $order instanceof Order) {
            throw OrderException::notFound();
        }

        if ($order->status !== OrderStatus::Draft) {
            throw OrderException::notDraft();
        }

        return $order;
    }

    private function recalculate(Order $order): Order
    {
        $subtotal = (int) OrderItem::query()
            ->where('tenant_id', $order->tenant_id)
            ->where('order_id', $order->id)
            ->sum('line_subtotal_minor');

        $order->forceFill(['subtotal_minor' => $subtotal, 'total_minor' => $subtotal])->save();

        return $order->refresh();
    }
}
