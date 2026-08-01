<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Models;

use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $product_id
 * @property string $outlet_id
 * @property bool $available
 * @property int|null $price_minor
 */
final class ProductOutletAvailability extends Model
{
    use HasLowercaseUlids;

    protected $table = 'catalog_product_outlet_availabilities';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'available' => 'boolean',
            'price_minor' => 'integer',
        ];
    }
}
