<?php

declare(strict_types=1);

namespace App\Modules\OrderingChannel\Domain\Models;

use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $outlet_id
 * @property string $cart_id
 * @property string $product_id
 * @property string|null $variant_id
 * @property string $quantity
 * @property list<string>|null $modifier_option_ids
 * @property string|null $notes
 */
final class OrderingCustomerCartItem extends Model
{
    use HasLowercaseUlids;

    protected $table = 'ordering_customer_cart_items';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'modifier_option_ids' => 'array',
        ];
    }
}
