<?php

declare(strict_types=1);

namespace App\Modules\Recipe\Domain\Models;

use App\Modules\Recipe\Domain\Enums\RecipeVersionStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $recipe_id
 * @property int $version_number
 * @property RecipeVersionStatus $status
 * @property string $yield_quantity
 * @property int $yield_percent
 * @property int|null $cost_minor
 * @property string $currency
 */
final class RecipeVersion extends Model
{
    use HasLowercaseUlids;

    protected $table = 'recipe_versions';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'status' => RecipeVersionStatus::class,
            'yield_percent' => 'integer',
            'cost_minor' => 'integer',
        ];
    }
}
