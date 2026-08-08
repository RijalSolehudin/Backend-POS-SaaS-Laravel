<?php

declare(strict_types=1);

namespace App\Modules\OrderingChannel\Domain\Models;

use App\Modules\OrderingChannel\Domain\Enums\OrderRequestStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $outlet_id
 * @property string $cart_id
 * @property string|null $table_session_id
 * @property string|null $sales_order_id
 * @property OrderRequestStatus $status
 * @property string|null $idempotency_key
 * @property CarbonImmutable $expires_at
 */
final class OrderingOrderRequest extends Model
{
    use HasLowercaseUlids;

    protected $table = 'ordering_order_requests';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => OrderRequestStatus::class,
            'expires_at' => 'immutable_datetime',
            'confirmed_at' => 'immutable_datetime',
            'rejected_at' => 'immutable_datetime',
        ];
    }
}
