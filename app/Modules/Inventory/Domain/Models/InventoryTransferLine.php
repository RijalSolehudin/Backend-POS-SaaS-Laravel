<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\Models;

use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $transfer_id
 * @property string $item_id
 * @property string $unit_id
 * @property string $quantity
 */
final class InventoryTransferLine extends Model
{
    use HasLowercaseUlids;

    protected $table = 'inventory_transfer_lines';

    protected $guarded = [];
}
