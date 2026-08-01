<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Domain\Models;

use App\Modules\Procurement\Domain\Enums\GoodsReceiptStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $outlet_id
 * @property string $purchase_order_id
 * @property string $receipt_number
 * @property GoodsReceiptStatus $status
 * @property string $received_by_user_id
 */
final class GoodsReceipt extends Model
{
    use HasLowercaseUlids;

    protected $table = 'procurement_goods_receipts';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => GoodsReceiptStatus::class,
            'received_at' => 'immutable_datetime',
        ];
    }
}
