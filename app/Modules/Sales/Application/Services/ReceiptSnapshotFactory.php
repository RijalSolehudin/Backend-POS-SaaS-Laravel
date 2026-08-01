<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Services;

use App\Modules\Sales\Domain\Models\Order;
use App\Modules\Sales\Domain\Models\OrderItem;
use App\Modules\Sales\Domain\Models\Payment;
use App\Modules\Tenancy\Application\Contracts\TenantCatalogReference;
use App\Modules\Tenancy\Application\Contracts\TenantUserDirectory;

final readonly class ReceiptSnapshotFactory
{
    public function __construct(
        private TenantCatalogReference $tenancy,
        private TenantUserDirectory $users,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function make(Order $order, Payment $payment): array
    {
        $order->loadMissing('items');

        $tenant = $this->tenancy->tenant($order->tenant_id);
        $outlet = $this->tenancy->activeOutlet($order->tenant_id, $order->outlet_id);
        $cashier = $this->users->findForTenant($order->tenant_id, $order->user_id);

        return [
            'tenant' => [
                'id' => $order->tenant_id,
                'name' => $tenant === null ? '' : $tenant->name,
            ],
            'outlet' => [
                'id' => $order->outlet_id,
                'name' => $outlet === null ? '' : $outlet->name,
                'code' => $outlet === null ? '' : $outlet->code,
            ],
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'completed_at' => $order->completed_at?->toJSON(),
                'cashier' => [
                    'id' => $order->user_id,
                    'name' => $cashier === null ? '' : $cashier->name,
                ],
                'items' => $order->items
                    ->map(fn (OrderItem $item): array => [
                        'product_id' => $item->product_id,
                        'sku' => $item->product_sku,
                        'name' => $item->product_name,
                        'category_id' => $item->product_category_id,
                        'category_name' => $item->product_category_name,
                        'quantity' => $item->quantity,
                        'unit_price_minor' => $item->unit_price_minor,
                        'line_subtotal_minor' => $item->line_subtotal_minor,
                        'currency' => $item->currency,
                    ])
                    ->values()
                    ->all(),
                'subtotal_minor' => $order->subtotal_minor,
                'discount_minor' => $order->discount_minor,
                'service_charge_minor' => $order->service_charge_minor,
                'tax_minor' => $order->tax_minor,
                'total_minor' => $order->total_minor,
                'currency' => $order->currency,
            ],
            'payment' => [
                'id' => $payment->id,
                'method' => $payment->method->value,
                'amount_minor' => $payment->amount_minor,
                'currency' => $payment->currency,
                'recorded_at' => $payment->recorded_at->toJSON(),
            ],
        ];
    }
}
