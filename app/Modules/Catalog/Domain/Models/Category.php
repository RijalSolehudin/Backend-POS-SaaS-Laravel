<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Models;

use App\Modules\Catalog\Domain\Enums\CategoryStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string|null $parent_id
 * @property string $name
 * @property int $display_order
 * @property CategoryStatus $status
 */
final class Category extends Model
{
    use HasLowercaseUlids;

    protected $table = 'catalog_categories';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'status' => CategoryStatus::class,
        ];
    }
}
