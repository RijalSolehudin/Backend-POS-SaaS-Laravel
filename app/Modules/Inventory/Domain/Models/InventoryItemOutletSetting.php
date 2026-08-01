<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\Models;

use App\Modules\Inventory\Domain\Enums\InventoryStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $outlet_id
 * @property string $item_id
 * @property InventoryStatus $status
 * @property string $low_stock_threshold_quantity
 */
final class InventoryItemOutletSetting extends Model
{
    use HasLowercaseUlids;

    protected $table = 'inventory_item_outlet_settings';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => InventoryStatus::class,
        ];
    }
}
