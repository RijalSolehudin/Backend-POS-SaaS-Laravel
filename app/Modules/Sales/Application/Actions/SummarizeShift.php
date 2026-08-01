<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Actions;

use App\Modules\Sales\Application\Data\ShiftSummary;
use App\Modules\Sales\Application\Exceptions\ShiftException;
use App\Modules\Sales\Domain\Enums\OrderStatus;
use App\Modules\Sales\Domain\Enums\PaymentMethod;
use App\Modules\Sales\Domain\Enums\PaymentStatus;
use App\Modules\Sales\Domain\Models\Order;
use App\Modules\Sales\Domain\Models\Payment;
use App\Modules\Sales\Domain\Models\Shift;
use App\Modules\Tenancy\Application\Data\PosOutletContext;

final readonly class SummarizeShift
{
    public function handle(PosOutletContext $context, string $shiftId): ShiftSummary
    {
        $shift = Shift::query()
            ->where('tenant_id', $context->tenantId)
            ->where('outlet_id', $context->outletId)
            ->where('user_id', $context->userId)
            ->whereKey($shiftId)
            ->first();

        if (! $shift instanceof Shift) {
            throw ShiftException::notFound();
        }

        return $this->fromShift($shift);
    }

    public function fromShift(Shift $shift): ShiftSummary
    {
        $completedOrdersCount = Order::query()
            ->where('tenant_id', $shift->tenant_id)
            ->where('outlet_id', $shift->outlet_id)
            ->where('shift_id', $shift->id)
            ->where('status', OrderStatus::Completed)
            ->count();
        $grossSalesMinor = (int) Order::query()
            ->where('tenant_id', $shift->tenant_id)
            ->where('outlet_id', $shift->outlet_id)
            ->where('shift_id', $shift->id)
            ->where('status', OrderStatus::Completed)
            ->sum('total_minor');
        $recordedPaymentsMinor = $this->paymentSum($shift);
        $cashPaymentsMinor = $this->paymentSum($shift, PaymentMethod::Cash);
        $manualNonCashPaymentsMinor = $this->paymentSum($shift, PaymentMethod::ManualNonCash);
        $expectedCashMinor = $shift->opening_cash_minor + $cashPaymentsMinor;

        return new ShiftSummary(
            tenantId: $shift->tenant_id,
            outletId: $shift->outlet_id,
            shiftId: $shift->id,
            userId: $shift->user_id,
            status: $shift->status->value,
            openedAt: $shift->opened_at,
            closedAt: $shift->closed_at,
            openingCashMinor: $shift->opening_cash_minor,
            closingCashMinor: $shift->closing_cash_minor,
            expectedCashMinor: $expectedCashMinor,
            cashVarianceMinor: $shift->closing_cash_minor === null ? 0 : $shift->closing_cash_minor - $expectedCashMinor,
            completedOrdersCount: $completedOrdersCount,
            grossSalesMinor: $grossSalesMinor,
            recordedPaymentsMinor: $recordedPaymentsMinor,
            cashPaymentsMinor: $cashPaymentsMinor,
            manualNonCashPaymentsMinor: $manualNonCashPaymentsMinor,
            currency: $shift->currency,
        );
    }

    private function paymentSum(Shift $shift, ?PaymentMethod $method = null): int
    {
        $query = Payment::query()
            ->where('tenant_id', $shift->tenant_id)
            ->where('outlet_id', $shift->outlet_id)
            ->where('shift_id', $shift->id)
            ->where('status', PaymentStatus::Recorded);

        if ($method instanceof PaymentMethod) {
            $query->where('method', $method);
        }

        return (int) $query->sum('amount_minor');
    }
}
