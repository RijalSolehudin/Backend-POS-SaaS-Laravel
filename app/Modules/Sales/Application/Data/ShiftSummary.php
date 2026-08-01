<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Data;

use Carbon\CarbonImmutable;

final readonly class ShiftSummary
{
    public function __construct(
        public string $tenantId,
        public string $outletId,
        public string $shiftId,
        public string $userId,
        public string $status,
        public CarbonImmutable $openedAt,
        public ?CarbonImmutable $closedAt,
        public int $openingCashMinor,
        public ?int $closingCashMinor,
        public int $expectedCashMinor,
        public int $cashVarianceMinor,
        public int $completedOrdersCount,
        public int $grossSalesMinor,
        public int $refundsMinor,
        public int $netSalesMinor,
        public int $recordedPaymentsMinor,
        public int $cashPaymentsMinor,
        public int $manualNonCashPaymentsMinor,
        public int $cashInMinor,
        public int $cashOutMinor,
        public string $currency,
    ) {}
}
