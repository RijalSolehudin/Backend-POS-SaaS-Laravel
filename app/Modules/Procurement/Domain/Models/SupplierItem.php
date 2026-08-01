<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Domain\Models;

use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $supplier_id
 * @property string $inventory_item_id
 * @property string $supplier_sku
 * @property int $last_price_minor
 * @property string $currency
 * @property bool $is_active
 */
final class SupplierItem extends Model
{
    use HasLowercaseUlids;

    protected $table = 'procurement_supplier_items';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'last_price_minor' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
