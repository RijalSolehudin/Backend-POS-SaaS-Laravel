<?php

declare(strict_types=1);

namespace App\Modules\Promotion\Domain\Models;

use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $outlet_id
 * @property string $sales_order_id
 * @property string $promotion_rule_id
 * @property string $promotion_name
 * @property string $promotion_type
 * @property int $promotion_value
 * @property int $discount_amount_minor
 * @property string $source
 * @property string|null $reason
 */
final class SalesOrderDiscount extends Model
{
    use HasLowercaseUlids;

    protected $table = 'sales_order_discounts';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'promotion_value' => 'integer',
            'discount_amount_minor' => 'integer',
        ];
    }
}
