<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Actions;

use App\Modules\Catalog\Application\Actions\GetAvailableOutletCatalogProduct;
use App\Modules\Catalog\Application\Data\AvailableCatalogVariant;
use App\Modules\Catalog\Application\Data\AvailableModifierGroup;
use App\Modules\Catalog\Application\Data\AvailableModifierOption;
use App\Modules\Sales\Application\Data\OrderItemSelection;
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

    public function handle(PosOutletContext $context, string $orderId, OrderItemSelection $selection): Order
    {
        return DB::transaction(function () use ($context, $orderId, $selection): Order {
            $order = $this->draftOrder($context, $orderId);
            $product = $this->catalog->handle($context, $selection->productId);

            if ($product === null) {
                throw OrderException::productUnavailable();
            }

            $variant = $this->selectedVariant($product->variants, $selection->variantId);
            $modifierSnapshot = $this->selectedModifiers($variant, $selection->modifierOptionIds);
            $modifierTotal = array_sum(array_column($modifierSnapshot, 'price_delta_minor'));
            $unitPrice = $variant->priceMinor + $modifierTotal;

            OrderItem::query()->create([
                'tenant_id' => $context->tenantId,
                'order_id' => $order->id,
                'product_id' => $product->id,
                'variant_id' => $variant->id === $product->id ? null : $variant->id,
                'product_sku' => $product->sku,
                'variant_sku' => $variant->sku,
                'product_name' => $product->name,
                'variant_name' => $variant->name,
                'product_category_id' => $product->categoryId,
                'product_category_name' => $product->categoryName,
                'quantity' => $selection->quantity,
                'unit_price_minor' => $unitPrice,
                'modifier_total_minor' => $modifierTotal,
                'modifier_snapshot' => $modifierSnapshot,
                'line_subtotal_minor' => $this->calculator->lineSubtotalMinor($unitPrice, $selection->quantity),
                'currency' => $variant->currency,
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

    /**
     * @param  list<AvailableCatalogVariant>  $variants
     */
    private function selectedVariant(array $variants, ?string $variantId): AvailableCatalogVariant
    {
        if ($variants === []) {
            throw OrderException::variantUnavailable();
        }

        if ($variantId === null) {
            return $variants[0];
        }

        foreach ($variants as $variant) {
            if ($variant->id === $variantId) {
                return $variant;
            }
        }

        throw OrderException::variantUnavailable();
    }

    /**
     * @param  list<string>  $selectedOptionIds
     * @return list<array{group_id: string, group_name: string, option_id: string, option_name: string, price_delta_minor: int, currency: string}>
     */
    private function selectedModifiers(AvailableCatalogVariant $variant, array $selectedOptionIds): array
    {
        $selectedCounts = array_count_values($selectedOptionIds);
        $snapshot = [];

        foreach ($variant->modifierGroups as $group) {
            $selectedOptions = $this->selectedGroupOptions($group, $selectedCounts);
            $count = count($selectedOptions);

            if ($group->required && $count === 0) {
                throw OrderException::modifierSelectionInvalid();
            }

            if ($count < $group->minSelection || $count > $group->maxSelection) {
                throw OrderException::modifierSelectionInvalid();
            }

            foreach ($selectedOptions as $option) {
                $snapshot[] = [
                    'group_id' => $group->id,
                    'group_name' => $group->name,
                    'option_id' => $option->id,
                    'option_name' => $option->name,
                    'price_delta_minor' => $option->priceDeltaMinor,
                    'currency' => $option->currency,
                ];

                unset($selectedCounts[$option->id]);
            }
        }

        if ($selectedCounts !== []) {
            throw OrderException::modifierSelectionInvalid();
        }

        return $snapshot;
    }

    /**
     * @param  array<string, int>  $selectedCounts
     * @return list<AvailableModifierOption>
     */
    private function selectedGroupOptions(AvailableModifierGroup $group, array $selectedCounts): array
    {
        $selected = [];

        foreach ($group->options as $option) {
            if (! array_key_exists($option->id, $selectedCounts)) {
                continue;
            }

            if ($selectedCounts[$option->id] !== 1) {
                throw OrderException::modifierSelectionInvalid();
            }

            $selected[] = $option;
        }

        return $selected;
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
