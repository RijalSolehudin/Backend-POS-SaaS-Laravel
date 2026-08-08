<?php

declare(strict_types=1);

namespace App\Modules\Kitchen\Domain\Models;

use App\Modules\Kitchen\Domain\Enums\KitchenRoutingRuleType;
use App\Modules\Kitchen\Domain\Enums\KitchenStatus;
use App\Shared\Domain\Concerns\HasLowercaseUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $outlet_id
 * @property string $station_id
 * @property KitchenRoutingRuleType $rule_type
 * @property string $catalog_reference_id
 * @property int $priority
 * @property KitchenStatus $status
 */
final class KitchenRoutingRule extends Model
{
    use HasLowercaseUlids;

    protected $table = 'kitchen_routing_rules';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'rule_type' => KitchenRoutingRuleType::class,
            'priority' => 'integer',
            'status' => KitchenStatus::class,
        ];
    }
}
