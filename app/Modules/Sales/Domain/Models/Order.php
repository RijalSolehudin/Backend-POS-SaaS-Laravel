<?php

declare(strict_types=1);

namespace App\Modules\Sales\Domain\Models;

use App\Modules\Sales\Domain\Enums\OrderStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $outlet_id
 * @property string $shift_id
 * @property string $user_id
 * @property string $order_number
 * @property OrderStatus $status
 * @property int $subtotal_minor
 * @property int $discount_minor
 * @property int $service_charge_minor
 * @property int $tax_minor
 * @property int $total_minor
 * @property string $currency
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable|null $cancelled_at
 * @property CarbonImmutable|null $voided_at
 */
final class Order extends Model
{
    use HasLowercaseUlids;

    protected $table = 'sales_orders';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal_minor' => 'integer',
            'discount_minor' => 'integer',
            'service_charge_minor' => 'integer',
            'tax_minor' => 'integer',
            'total_minor' => 'integer',
            'completed_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
            'voided_at' => 'immutable_datetime',
        ];
    }
}
