<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Contracts;

use App\Modules\Sales\Application\Data\DiningOrderSummary;

interface DiningOrderReference
{
    public function order(string $tenantId, string $outletId, string $orderId): ?DiningOrderSummary;
}
