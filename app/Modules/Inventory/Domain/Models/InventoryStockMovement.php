<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\Models;

use App\Modules\Inventory\Domain\Enums\StockMovementType;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $outlet_id
 * @property string $item_id
 * @property string $unit_id
 * @property string|null $actor_user_id
 * @property StockMovementType $movement_type
 * @property string $source_type
 * @property string|null $source_id
 * @property string|null $opening_balance_key
 * @property string $quantity
 * @property int|null $unit_cost_minor
 * @property int $total_cost_minor
 * @property string $currency
 * @property string $balance_quantity_after
 * @property int $balance_total_cost_minor_after
 * @property string|null $reason
 * @property string $idempotency_key
 * @property CarbonImmutable $occurred_at
 */
final class InventoryStockMovement extends Model
{
    use HasLowercaseUlids;

    protected $table = 'inventory_stock_movements';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'movement_type' => StockMovementType::class,
            'unit_cost_minor' => 'integer',
            'total_cost_minor' => 'integer',
            'balance_total_cost_minor_after' => 'integer',
            'occurred_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        self::updating(fn (): bool => false);
        self::deleting(fn (): bool => false);
    }
}
