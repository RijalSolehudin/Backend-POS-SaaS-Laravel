<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\Models;

use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $outlet_id
 * @property string $item_id
 * @property string $unit_id
 * @property string $quantity
 * @property int $total_cost_minor
 * @property string $currency
 */
final class InventoryBalance extends Model
{
    use HasLowercaseUlids;

    protected $table = 'inventory_balances';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'total_cost_minor' => 'integer',
        ];
    }
}
