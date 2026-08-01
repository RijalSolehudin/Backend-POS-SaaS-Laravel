<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Actions;

use App\Modules\Sales\Application\Data\DailySalesSummary;
use App\Modules\Sales\Domain\Enums\OrderStatus;
use App\Modules\Sales\Domain\Enums\PaymentMethod;
use App\Modules\Sales\Domain\Enums\PaymentStatus;
use App\Modules\Sales\Domain\Models\Order;
use App\Modules\Sales\Domain\Models\Payment;
use App\Modules\Tenancy\Application\Contracts\TenantCatalogReference;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

final readonly class SummarizeDailySales
{
    public function __construct(private TenantCatalogReference $tenancy) {}

    public function handle(string $tenantId, string $businessDate): DailySalesSummary
    {
        $tenant = $this->tenancy->tenant($tenantId);
        $timezone = $tenant === null ? (string) config('app.timezone') : $tenant->timezone;
        $localStart = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $businessDate.' 00:00:00', $timezone);

        if (! $localStart instanceof CarbonImmutable) {
            throw new InvalidArgumentException('Business date must use Y-m-d format.');
        }

        $start = $localStart->utc();
        $end = $start->addDay();

        $orders = Order::query()
            ->where('tenant_id', $tenantId)
            ->where('status', OrderStatus::Completed)
            ->where('completed_at', '>=', $start)
            ->where('completed_at', '<', $end);
        $completedOrdersCount = (clone $orders)->count();
        $grossSalesMinor = (int) (clone $orders)->sum('total_minor');

        $payments = Payment::query()
            ->where('tenant_id', $tenantId)
            ->where('status', PaymentStatus::Recorded)
            ->where('recorded_at', '>=', $start)
            ->where('recorded_at', '<', $end);
        $recordedPaymentsMinor = (int) (clone $payments)->sum('amount_minor');
        $cashPaymentsMinor = (int) (clone $payments)->where('method', PaymentMethod::Cash)->sum('amount_minor');
        $manualNonCashPaymentsMinor = (int) (clone $payments)->where('method', PaymentMethod::ManualNonCash)->sum('amount_minor');

        return new DailySalesSummary(
            tenantId: $tenantId,
            businessDate: $businessDate,
            completedOrdersCount: $completedOrdersCount,
            grossSalesMinor: $grossSalesMinor,
            recordedPaymentsMinor: $recordedPaymentsMinor,
            cashPaymentsMinor: $cashPaymentsMinor,
            manualNonCashPaymentsMinor: $manualNonCashPaymentsMinor,
            currency: $tenant === null ? 'IDR' : $tenant->currency,
            outlets: $this->outletRows($tenantId, $start, $end),
        );
    }

    /**
     * @return list<array{outlet_id: string, outlet_name: string, completed_orders_count: int, gross_sales_minor: int, recorded_payments_minor: int, cash_payments_minor: int, manual_non_cash_payments_minor: int}>
     */
    private function outletRows(string $tenantId, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $outlets = $this->tenancy->activeOutlets($tenantId);

        return array_map(function ($outlet) use ($tenantId, $start, $end): array {
            $orders = Order::query()
                ->where('tenant_id', $tenantId)
                ->where('outlet_id', $outlet->outletId)
                ->where('status', OrderStatus::Completed)
                ->where('completed_at', '>=', $start)
                ->where('completed_at', '<', $end);
            $payments = Payment::query()
                ->where('tenant_id', $tenantId)
                ->where('outlet_id', $outlet->outletId)
                ->where('status', PaymentStatus::Recorded)
                ->where('recorded_at', '>=', $start)
                ->where('recorded_at', '<', $end);

            return [
                'outlet_id' => $outlet->outletId,
                'outlet_name' => $outlet->name,
                'completed_orders_count' => (clone $orders)->count(),
                'gross_sales_minor' => (int) (clone $orders)->sum('total_minor'),
                'recorded_payments_minor' => (int) (clone $payments)->sum('amount_minor'),
                'cash_payments_minor' => (int) (clone $payments)->where('method', PaymentMethod::Cash)->sum('amount_minor'),
                'manual_non_cash_payments_minor' => (int) (clone $payments)->where('method', PaymentMethod::ManualNonCash)->sum('amount_minor'),
            ];
        }, $outlets);
    }
}
