<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Domain\Models;

use App\Modules\Procurement\Domain\Enums\PurchaseOrderStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $outlet_id
 * @property string $supplier_id
 * @property string $po_number
 * @property PurchaseOrderStatus $status
 * @property int $total_minor
 * @property string $currency
 * @property string|null $notes
 * @property string $created_by_user_id
 * @property string|null $submitted_by_user_id
 * @property string|null $approved_by_user_id
 * @property string|null $cancelled_by_user_id
 * @property string|null $cancel_reason
 */
final class PurchaseOrder extends Model
{
    use HasLowercaseUlids;

    protected $table = 'procurement_purchase_orders';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => PurchaseOrderStatus::class,
            'total_minor' => 'integer',
            'submitted_at' => 'immutable_datetime',
            'approved_at' => 'immutable_datetime',
            'ordered_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
        ];
    }
}
