<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Actions;

use App\Modules\Catalog\Application\Actions\GetAvailableOutletCatalogProduct;
use App\Modules\Sales\Application\Exceptions\OrderException;
use App\Modules\Sales\Application\Services\QuantityCalculator;
use App\Modules\Sales\Domain\Enums\OrderStatus;
use App\Modules\Sales\Domain\Models\Order;
use App\Modules\Sales\Domain\Models\OrderItem;
use App\Modules\Tenancy\Application\Data\PosOutletContext;
use Illuminate\Support\Facades\DB;

final readonly class AddOrderItem
{
    public function __construct(
        private GetAvailableOutletCatalogProduct $catalog,
        private QuantityCalculator $calculator,
    ) {}

    public function handle(PosOutletContext $context, string $orderId, string $productId, string $quantity): Order
    {
        return DB::transaction(function () use ($context, $orderId, $productId, $quantity): Order {
            $order = $this->draftOrder($context, $orderId);
            $product = $this->catalog->handle($context, $productId);

            if ($product === null) {
                throw OrderException::productUnavailable();
            }

            OrderItem::query()->create([
                'tenant_id' => $context->tenantId,
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_sku' => $product->sku,
                'product_name' => $product->name,
                'product_category_id' => $product->categoryId,
                'product_category_name' => $product->categoryName,
                'quantity' => $quantity,
                'unit_price_minor' => $product->priceMinor,
                'line_subtotal_minor' => $this->calculator->lineSubtotalMinor($product->priceMinor, $quantity),
                'currency' => $product->currency,
            ]);

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

        $order->forceFill([
            'subtotal_minor' => $subtotal,
            'discount_minor' => 0,
            'service_charge_minor' => 0,
            'tax_minor' => 0,
            'total_minor' => $subtotal,
        ])->save();

        return $order->refresh();
    }
}
