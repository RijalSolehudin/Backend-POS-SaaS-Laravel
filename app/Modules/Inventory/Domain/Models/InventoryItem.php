<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\Models;

use App\Modules\Inventory\Domain\Enums\InventoryStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $unit_id
 * @property string $name
 * @property string $sku
 * @property InventoryStatus $status
 */
final class InventoryItem extends Model
{
    use HasLowercaseUlids;

    protected $table = 'inventory_items';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => InventoryStatus::class,
        ];
    }
}
