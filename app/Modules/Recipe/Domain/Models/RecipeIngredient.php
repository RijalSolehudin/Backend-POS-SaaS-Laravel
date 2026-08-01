<?php

declare(strict_types=1);

namespace App\Modules\Recipe\Domain\Models;

use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $recipe_version_id
 * @property string $inventory_item_id
 * @property string $unit_id
 * @property string $quantity
 * @property int|null $unit_cost_minor_snapshot
 * @property int|null $total_cost_minor_snapshot
 */
final class RecipeIngredient extends Model
{
    use HasLowercaseUlids;

    protected $table = 'recipe_ingredients';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'unit_cost_minor_snapshot' => 'integer',
            'total_cost_minor_snapshot' => 'integer',
        ];
    }
}
