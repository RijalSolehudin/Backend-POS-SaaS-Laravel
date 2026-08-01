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
 * @property string $name
 * @property string $sku
 * @property int $price_minor
 * @property string $currency
 * @property bool $is_default
 * @property int $display_order
 * @property ProductStatus $status
 */
final class ProductVariant extends Model
{
    use HasLowercaseUlids;

    protected $table = 'catalog_product_variants';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'price_minor' => 'integer',
            'is_default' => 'boolean',
            'display_order' => 'integer',
            'status' => ProductStatus::class,
        ];
    }
}
