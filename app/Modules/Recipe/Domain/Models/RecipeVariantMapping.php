<?php

declare(strict_types=1);

namespace App\Modules\Recipe\Domain\Models;

use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $variant_id
 * @property string|null $recipe_version_id
 * @property bool $requires_recipe
 */
final class RecipeVariantMapping extends Model
{
    use HasLowercaseUlids;

    protected $table = 'recipe_variant_mappings';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'requires_recipe' => 'boolean',
        ];
    }
}
