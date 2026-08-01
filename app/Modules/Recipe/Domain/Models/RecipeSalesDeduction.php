<?php

declare(strict_types=1);

namespace App\Modules\Recipe\Domain\Models;

use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $outlet_id
 * @property string $order_id
 * @property string $order_item_id
 * @property string $recipe_version_id
 * @property array<string, mixed> $snapshot
 * @property int $total_cost_minor
 * @property string $currency
 */
final class RecipeSalesDeduction extends Model
{
    use HasLowercaseUlids;

    protected $table = 'recipe_sales_deductions';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'total_cost_minor' => 'integer',
        ];
    }
}
