<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\Models;

use App\Modules\Inventory\Domain\Enums\TransferStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $source_outlet_id
 * @property string $destination_outlet_id
 * @property string $requested_by_user_id
 * @property string|null $approval_id
 * @property string|null $dispatched_by_user_id
 * @property string|null $received_by_user_id
 * @property string|null $cancelled_by_user_id
 * @property TransferStatus $status
 * @property string $reason
 * @property CarbonImmutable|null $requested_at
 * @property CarbonImmutable|null $approved_at
 * @property CarbonImmutable|null $dispatched_at
 * @property CarbonImmutable|null $received_at
 * @property CarbonImmutable|null $cancelled_at
 */
final class InventoryTransfer extends Model
{
    use HasLowercaseUlids;

    protected $table = 'inventory_transfers';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => TransferStatus::class,
            'requested_at' => 'immutable_datetime',
            'approved_at' => 'immutable_datetime',
            'dispatched_at' => 'immutable_datetime',
            'received_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
        ];
    }
}
