<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Domain\Models;

use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $purchase_return_id
 * @property string $goods_receipt_line_id
 * @property string $inventory_item_id
 * @property string $unit_id
 * @property string $quantity
 */
final class PurchaseReturnLine extends Model
{
    use HasLowercaseUlids;

    protected $table = 'procurement_purchase_return_lines';

    protected $guarded = [];
}
