<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Models;

use App\Modules\Catalog\Domain\Enums\ProductStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $group_id
 * @property string $name
 * @property int $price_delta_minor
 * @property string $currency
 * @property int $display_order
 * @property ProductStatus $status
 */
final class ModifierOption extends Model
{
    use HasLowercaseUlids;

    protected $table = 'catalog_modifier_options';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'price_delta_minor' => 'integer',
            'display_order' => 'integer',
            'status' => ProductStatus::class,
        ];
    }
}
