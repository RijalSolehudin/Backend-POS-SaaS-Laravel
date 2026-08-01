<?php

declare(strict_types=1);

namespace App\Modules\Sales\Domain\Models;

use App\Modules\Sales\Domain\Enums\PaymentMethod;
use App\Modules\Sales\Domain\Enums\PaymentStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $outlet_id
 * @property string $order_id
 * @property string $shift_id
 * @property PaymentMethod $method
 * @property PaymentStatus $status
 * @property int $amount_minor
 * @property string $currency
 * @property CarbonImmutable $recorded_at
 * @property CarbonImmutable|null $voided_at
 */
final class Payment extends Model
{
    use HasLowercaseUlids;

    protected $table = 'sales_payments';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'amount_minor' => 'integer',
            'recorded_at' => 'immutable_datetime',
            'voided_at' => 'immutable_datetime',
        ];
    }
}
