<?php

declare(strict_types=1);

namespace App\Modules\Sales\Domain\Models;

use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $order_id
 * @property string $product_id
 * @property string|null $variant_id
 * @property string $product_sku
 * @property string|null $variant_sku
 * @property string $product_name
 * @property string|null $variant_name
 * @property string $product_category_id
 * @property string $product_category_name
 * @property string $quantity
 * @property int $unit_price_minor
 * @property int $modifier_total_minor
 * @property array<int, array<string, mixed>>|null $modifier_snapshot
 * @property int $line_subtotal_minor
 * @property string $currency
 */
final class OrderItem extends Model
{
    use HasLowercaseUlids;

    protected $table = 'sales_order_items';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'unit_price_minor' => 'integer',
            'modifier_total_minor' => 'integer',
            'modifier_snapshot' => 'array',
            'line_subtotal_minor' => 'integer',
        ];
    }
}
