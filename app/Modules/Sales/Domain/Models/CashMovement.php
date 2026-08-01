<?php

declare(strict_types=1);

namespace App\Modules\Sales\Domain\Models;

use App\Modules\Sales\Domain\Enums\CashMovementType;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $outlet_id
 * @property string $shift_id
 * @property string $user_id
 * @property string|null $approval_id
 * @property CashMovementType $type
 * @property int $amount_minor
 * @property string $currency
 * @property string $reason
 * @property CarbonImmutable $recorded_at
 */
final class CashMovement extends Model
{
    use HasLowercaseUlids;

    protected $table = 'sales_cash_movements';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'type' => CashMovementType::class,
            'amount_minor' => 'integer',
            'recorded_at' => 'immutable_datetime',
        ];
    }
}
