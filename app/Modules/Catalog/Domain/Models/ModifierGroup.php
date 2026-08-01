<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Models;

use App\Modules\Catalog\Domain\Enums\ProductStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $product_id
 * @property string|null $variant_id
 * @property string $name
 * @property bool $required
 * @property int $min_selection
 * @property int $max_selection
 * @property int $display_order
 * @property ProductStatus $status
 */
final class ModifierGroup extends Model
{
    use HasLowercaseUlids;

    protected $table = 'catalog_modifier_groups';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'required' => 'boolean',
            'min_selection' => 'integer',
            'max_selection' => 'integer',
            'display_order' => 'integer',
            'status' => ProductStatus::class,
        ];
    }
}
