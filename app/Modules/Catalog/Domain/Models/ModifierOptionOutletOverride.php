<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Models;

use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $option_id
 * @property string $outlet_id
 * @property bool $available
 * @property int|null $price_delta_minor
 */
final class ModifierOptionOutletOverride extends Model
{
    use HasLowercaseUlids;

    protected $table = 'catalog_modifier_option_outlet_overrides';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'available' => 'boolean',
            'price_delta_minor' => 'integer',
        ];
    }
}
