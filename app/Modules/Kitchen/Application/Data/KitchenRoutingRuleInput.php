<?php

declare(strict_types=1);

namespace App\Modules\Kitchen\Application\Data;

use App\Modules\Kitchen\Domain\Enums\KitchenRoutingRuleType;

final readonly class KitchenRoutingRuleInput
{
    public function __construct(
        public string $outletId,
        public string $stationId,
        public KitchenRoutingRuleType $ruleType,
        public string $catalogReferenceId,
        public int $priority = 100,
    ) {}
}
