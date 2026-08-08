<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Contracts;

use App\Modules\Sales\Application\Data\KitchenOrderSummary;

interface KitchenOrderReference
{
    public function orderWithItems(string $tenantId, string $outletId, string $orderId): ?KitchenOrderSummary;
}
