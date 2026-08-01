<?php

declare(strict_types=1);

namespace App\Modules\Sales\Domain\Models;

use App\Modules\Sales\Domain\Enums\PaymentMethod;
use App\Modules\Sales\Domain\Enums\RefundStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $outlet_id
 * @property string $shift_id
 * @property string $order_id
 * @property string $payment_id
 * @property string $approval_id
 * @property string $refunded_by
 * @property PaymentMethod $method
 * @property RefundStatus $status
 * @property int $amount_minor
 * @property string $currency
 * @property string $reason
 * @property CarbonImmutable $recorded_at
 */
final class Refund extends Model
{
    use HasLowercaseUlids;

    protected $table = 'sales_refunds';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'status' => RefundStatus::class,
            'amount_minor' => 'integer',
            'recorded_at' => 'immutable_datetime',
        ];
    }
}
