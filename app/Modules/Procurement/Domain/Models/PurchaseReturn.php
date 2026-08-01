<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Domain\Models;

use App\Modules\Procurement\Domain\Enums\PurchaseReturnStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $outlet_id
 * @property string $goods_receipt_id
 * @property string $return_number
 * @property PurchaseReturnStatus $status
 * @property string $returned_by_user_id
 * @property string $reason
 */
final class PurchaseReturn extends Model
{
    use HasLowercaseUlids;

    protected $table = 'procurement_purchase_returns';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => PurchaseReturnStatus::class,
            'returned_at' => 'immutable_datetime',
        ];
    }
}
