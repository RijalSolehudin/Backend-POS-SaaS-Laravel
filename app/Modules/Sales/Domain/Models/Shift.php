<?php

declare(strict_types=1);

namespace App\Modules\Sales\Domain\Models;

use App\Modules\Sales\Domain\Enums\ShiftStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $outlet_id
 * @property string $user_id
 * @property string|null $open_shift_key
 * @property ShiftStatus $status
 * @property CarbonImmutable $opened_at
 * @property CarbonImmutable|null $closed_at
 * @property CarbonImmutable|null $voided_at
 * @property int $opening_cash_minor
 * @property int|null $closing_cash_minor
 * @property int $expected_cash_minor
 * @property int $gross_sales_minor
 * @property string $currency
 */
final class Shift extends Model
{
    use HasLowercaseUlids;

    protected $table = 'sales_shifts';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => ShiftStatus::class,
            'opened_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
            'voided_at' => 'immutable_datetime',
            'opening_cash_minor' => 'integer',
            'closing_cash_minor' => 'integer',
            'expected_cash_minor' => 'integer',
            'gross_sales_minor' => 'integer',
        ];
    }
}
