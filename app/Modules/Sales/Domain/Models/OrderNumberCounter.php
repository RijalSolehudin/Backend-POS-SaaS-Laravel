<?php

declare(strict_types=1);

namespace App\Modules\Sales\Domain\Models;

use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $outlet_id
 * @property CarbonImmutable $business_date
 * @property int $next_sequence
 */
final class OrderNumberCounter extends Model
{
    use HasLowercaseUlids;

    protected $table = 'sales_order_number_counters';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'business_date' => 'immutable_date',
            'next_sequence' => 'integer',
        ];
    }
}
