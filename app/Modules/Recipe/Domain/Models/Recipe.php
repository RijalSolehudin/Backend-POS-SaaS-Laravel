<?php

declare(strict_types=1);

namespace App\Modules\Recipe\Domain\Models;

use App\Modules\Recipe\Domain\Enums\RecipeStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property string $sku
 * @property RecipeStatus $status
 * @property bool $requires_recipe
 */
final class Recipe extends Model
{
    use HasLowercaseUlids;

    protected $table = 'recipe_recipes';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => RecipeStatus::class,
            'requires_recipe' => 'boolean',
        ];
    }
}
