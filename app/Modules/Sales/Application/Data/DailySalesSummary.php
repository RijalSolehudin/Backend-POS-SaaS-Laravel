<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Data;

final readonly class DailySalesSummary
{
    /**
     * @param  list<array{outlet_id: string, outlet_name: string, completed_orders_count: int, gross_sales_minor: int, refunds_minor: int, net_sales_minor: int, recorded_payments_minor: int, cash_payments_minor: int, manual_non_cash_payments_minor: int}>  $outlets
     */
    public function __construct(
        public string $tenantId,
        public string $businessDate,
        public int $completedOrdersCount,
        public int $grossSalesMinor,
        public int $refundsMinor,
        public int $netSalesMinor,
        public int $recordedPaymentsMinor,
        public int $cashPaymentsMinor,
        public int $manualNonCashPaymentsMinor,
        public string $currency,
        public array $outlets,
    ) {}
}
