<?php

declare(strict_types=1);

namespace App\Modules\Promotion\Domain\Models;

use App\Modules\Promotion\Domain\Enums\PromotionDiscountType;
use App\Modules\Promotion\Domain\Enums\PromotionStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string|null $outlet_id
 * @property string $name
 * @property string $code
 * @property PromotionDiscountType $discount_type
 * @property int $discount_value
 * @property PromotionStatus $status
 */
final class PromotionRule extends Model
{
    use HasLowercaseUlids;

    protected $table = 'promotion_rules';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'discount_type' => PromotionDiscountType::class,
            'discount_value' => 'integer',
            'status' => PromotionStatus::class,
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
        ];
    }
}
