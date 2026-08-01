<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Domain\Models;

use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $goods_receipt_id
 * @property string $purchase_order_line_id
 * @property string $inventory_item_id
 * @property string $unit_id
 * @property string $quantity
 * @property string $returned_quantity
 * @property int $unit_cost_minor
 * @property int $total_cost_minor
 */
final class GoodsReceiptLine extends Model
{
    use HasLowercaseUlids;

    protected $table = 'procurement_goods_receipt_lines';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'unit_cost_minor' => 'integer',
            'total_cost_minor' => 'integer',
        ];
    }
}
