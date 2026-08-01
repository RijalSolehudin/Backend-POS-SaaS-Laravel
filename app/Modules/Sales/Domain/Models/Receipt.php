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
 * @property string $order_id
 * @property string $payment_id
 * @property string $receipt_number
 * @property CarbonImmutable $issued_at
 * @property array<string, mixed> $snapshot
 */
final class Receipt extends Model
{
    use HasLowercaseUlids;

    protected $table = 'sales_receipts';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'issued_at' => 'immutable_datetime',
            'snapshot' => 'array',
        ];
    }
}
