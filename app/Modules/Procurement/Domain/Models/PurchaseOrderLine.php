<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Domain\Models;

use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $purchase_order_id
 * @property string $supplier_item_id
 * @property string $inventory_item_id
 * @property string $unit_id
 * @property string $quantity
 * @property string $received_quantity
 * @property int $unit_price_minor
 * @property int $line_total_minor
 */
final class PurchaseOrderLine extends Model
{
    use HasLowercaseUlids;

    protected $table = 'procurement_purchase_order_lines';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'unit_price_minor' => 'integer',
            'line_total_minor' => 'integer',
        ];
    }
}
