<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\Models;

use App\Modules\Inventory\Domain\Enums\InventoryStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property string $symbol
 * @property int $precision
 * @property InventoryStatus $status
 */
final class InventoryUnit extends Model
{
    use HasLowercaseUlids;

    protected $table = 'inventory_units';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'precision' => 'integer',
            'status' => InventoryStatus::class,
        ];
    }
}
