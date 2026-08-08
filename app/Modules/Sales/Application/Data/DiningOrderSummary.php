<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Data;

final readonly class DiningOrderSummary
{
    public function __construct(
        public string $orderId,
        public string $tenantId,
        public string $outletId,
        public string $status,
    ) {}

    public function isTerminal(): bool
    {
        return in_array($this->status, ['completed', 'cancelled', 'voided'], true);
    }
}
