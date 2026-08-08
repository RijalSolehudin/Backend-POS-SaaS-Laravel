<?php

declare(strict_types=1);

namespace App\Modules\OrderingChannel\Application\Actions;

use App\Modules\Catalog\Application\Actions\GetAvailableOutletCatalogProduct;
use App\Modules\OrderingChannel\Application\Data\CustomerCartItemInput;
use App\Modules\OrderingChannel\Application\Exceptions\OrderingChannelException;
use App\Modules\OrderingChannel\Domain\Models\OrderingCustomerCart;
use App\Modules\OrderingChannel\Domain\Models\OrderingCustomerCartItem;
use App\Modules\Tenancy\Application\Data\PosOutletContext;

final readonly class AddCustomerCartItem
{
    public function __construct(private GetAvailableOutletCatalogProduct $catalog) {}

    public function handle(OrderingCustomerCart $cart, CustomerCartItemInput $input): OrderingCustomerCartItem
    {
        $product = $this->catalog->handle(new PosOutletContext($cart->tenant_id, $cart->outlet_id, 'public-qr', 'public-qr'), $input->productId);

        if ($product === null) {
            throw OrderingChannelException::cartInvalid();
        }

        $variantFound = $input->variantId === null;

        foreach ($product->variants as $variant) {
            if ($input->variantId === null || $variant->id === $input->variantId) {
                $variantFound = true;
                break;
            }
        }

        if (! $variantFound) {
            throw OrderingChannelException::cartInvalid();
        }

        return OrderingCustomerCartItem::query()->create([
            'tenant_id' => $cart->tenant_id,
            'outlet_id' => $cart->outlet_id,
            'cart_id' => $cart->id,
            'product_id' => $input->productId,
            'variant_id' => $input->variantId,
            'quantity' => $input->quantity,
            'modifier_option_ids' => $input->modifierOptionIds,
            'notes' => $input->notes,
        ]);
    }
}
