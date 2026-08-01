<?php

declare(strict_types=1);

namespace App\Modules\Sales\Presentation\Console\Commands;

use App\Modules\Sales\Domain\Enums\CashMovementType;
use App\Modules\Sales\Domain\Enums\OrderStatus;
use App\Modules\Sales\Domain\Enums\PaymentStatus;
use App\Modules\Sales\Domain\Enums\SensitiveActionApprovalStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class SalesRecoveryCheckCommand extends Command
{
    protected $signature = 'sales:recovery-check {--json : Output findings as JSON}';

    protected $description = 'Find ambiguous Sales financial states that require operator review.';

    /**
     * @var array<string, string>
     */
    private const RESOURCE_TABLES = [
        'sales_cash_movement' => 'sales_cash_movements',
        'sales_order' => 'sales_orders',
        'sales_payment' => 'sales_payments',
        'sales_refund' => 'sales_refunds',
        'sales_sensitive_action_approval' => 'sales_sensitive_action_approvals',
    ];

    public function handle(): int
    {
        $findings = [
            ...$this->completedOrdersWithoutRecordedPayment(),
            ...$this->completedOrdersWithoutReceipt(),
            ...$this->recordedPaymentsForNonCompletedOrders(),
            ...$this->refundsWithoutConsumedApproval(),
            ...$this->cashOutMovementsWithoutConsumedApproval(),
            ...$this->missingIdempotencyResources(),
        ];

        if ($this->option('json')) {
            $this->line((string) json_encode([
                'findings_count' => count($findings),
                'findings' => $findings,
            ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        } elseif ($findings === []) {
            $this->info('No Sales recovery findings detected.');
        } else {
            $this->warn(sprintf('%d Sales recovery finding(s) require operator review.', count($findings)));
            $this->table(
                ['code', 'severity', 'tenant_id', 'outlet_id', 'resource_type', 'resource_id', 'detail'],
                $findings,
            );
        }

        return $findings === [] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @return array<int, array{code: string, severity: string, tenant_id: string, outlet_id: string|null, resource_type: string, resource_id: string, detail: string}>
     */
    private function completedOrdersWithoutRecordedPayment(): array
    {
        return DB::table('sales_orders')
            ->where('status', OrderStatus::Completed->value)
            ->orderBy('id')
            ->get()
            ->filter(fn (object $order): bool => DB::table('sales_payments')
                ->where('order_id', $order->id)
                ->where('status', PaymentStatus::Recorded->value)
                ->doesntExist())
            ->map(fn (object $order): array => $this->finding(
                code: 'ORDER_COMPLETED_WITHOUT_RECORDED_PAYMENT',
                tenantId: (string) $order->tenant_id,
                outletId: (string) $order->outlet_id,
                resourceType: 'sales_order',
                resourceId: (string) $order->id,
                detail: 'Completed order has no recorded payment.',
            ))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{code: string, severity: string, tenant_id: string, outlet_id: string|null, resource_type: string, resource_id: string, detail: string}>
     */
    private function completedOrdersWithoutReceipt(): array
    {
        return DB::table('sales_orders')
            ->where('status', OrderStatus::Completed->value)
            ->orderBy('id')
            ->get()
            ->filter(fn (object $order): bool => DB::table('sales_receipts')
                ->where('order_id', $order->id)
                ->doesntExist())
            ->map(fn (object $order): array => $this->finding(
                code: 'ORDER_COMPLETED_WITHOUT_RECEIPT',
                tenantId: (string) $order->tenant_id,
                outletId: (string) $order->outlet_id,
                resourceType: 'sales_order',
                resourceId: (string) $order->id,
                detail: 'Completed order has no receipt snapshot.',
            ))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{code: string, severity: string, tenant_id: string, outlet_id: string|null, resource_type: string, resource_id: string, detail: string}>
     */
    private function recordedPaymentsForNonCompletedOrders(): array
    {
        return DB::table('sales_payments')
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_payments.order_id')
            ->where('sales_payments.status', PaymentStatus::Recorded->value)
            ->where('sales_orders.status', '<>', OrderStatus::Completed->value)
            ->orderBy('sales_payments.id')
            ->get([
                'sales_payments.id',
                'sales_payments.tenant_id',
                'sales_payments.outlet_id',
                'sales_orders.id as order_id',
                'sales_orders.status as order_status',
            ])
            ->map(fn (object $payment): array => $this->finding(
                code: 'PAYMENT_RECORDED_FOR_NON_COMPLETED_ORDER',
                tenantId: (string) $payment->tenant_id,
                outletId: (string) $payment->outlet_id,
                resourceType: 'sales_payment',
                resourceId: (string) $payment->id,
                detail: sprintf('Recorded payment points to order %s with status %s.', $payment->order_id, $payment->order_status),
            ))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{code: string, severity: string, tenant_id: string, outlet_id: string|null, resource_type: string, resource_id: string, detail: string}>
     */
    private function refundsWithoutConsumedApproval(): array
    {
        return DB::table('sales_refunds')
            ->leftJoin('sales_sensitive_action_approvals', 'sales_sensitive_action_approvals.id', '=', 'sales_refunds.approval_id')
            ->where(function ($query): void {
                $query
                    ->whereNull('sales_sensitive_action_approvals.id')
                    ->orWhere('sales_sensitive_action_approvals.status', '<>', SensitiveActionApprovalStatus::Consumed->value);
            })
            ->orderBy('sales_refunds.id')
            ->get([
                'sales_refunds.id',
                'sales_refunds.tenant_id',
                'sales_refunds.outlet_id',
                'sales_refunds.approval_id',
                'sales_sensitive_action_approvals.status as approval_status',
            ])
            ->map(fn (object $refund): array => $this->finding(
                code: 'REFUND_APPROVAL_NOT_CONSUMED',
                tenantId: (string) $refund->tenant_id,
                outletId: (string) $refund->outlet_id,
                resourceType: 'sales_refund',
                resourceId: (string) $refund->id,
                detail: sprintf('Refund approval %s is %s.', $refund->approval_id, $refund->approval_status ?? 'missing'),
            ))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{code: string, severity: string, tenant_id: string, outlet_id: string|null, resource_type: string, resource_id: string, detail: string}>
     */
    private function cashOutMovementsWithoutConsumedApproval(): array
    {
        return DB::table('sales_cash_movements')
            ->leftJoin('sales_sensitive_action_approvals', 'sales_sensitive_action_approvals.id', '=', 'sales_cash_movements.approval_id')
            ->where('sales_cash_movements.type', CashMovementType::CashOut->value)
            ->where(function ($query): void {
                $query
                    ->whereNull('sales_sensitive_action_approvals.id')
                    ->orWhere('sales_sensitive_action_approvals.status', '<>', SensitiveActionApprovalStatus::Consumed->value);
            })
            ->orderBy('sales_cash_movements.id')
            ->get([
                'sales_cash_movements.id',
                'sales_cash_movements.tenant_id',
                'sales_cash_movements.outlet_id',
                'sales_cash_movements.approval_id',
                'sales_sensitive_action_approvals.status as approval_status',
            ])
            ->map(fn (object $movement): array => $this->finding(
                code: 'CASH_OUT_APPROVAL_NOT_CONSUMED',
                tenantId: (string) $movement->tenant_id,
                outletId: (string) $movement->outlet_id,
                resourceType: 'sales_cash_movement',
                resourceId: (string) $movement->id,
                detail: sprintf('Cash out approval %s is %s.', $movement->approval_id ?? 'missing', $movement->approval_status ?? 'missing'),
            ))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{code: string, severity: string, tenant_id: string, outlet_id: string|null, resource_type: string, resource_id: string, detail: string}>
     */
    private function missingIdempotencyResources(): array
    {
        return DB::table('sales_idempotency_records')
            ->whereNotNull('resource_type')
            ->whereNotNull('resource_id')
            ->orderBy('id')
            ->get()
            ->filter(function (object $record): bool {
                $table = self::RESOURCE_TABLES[(string) $record->resource_type] ?? null;

                return $table !== null
                    && DB::table($table)->where('id', $record->resource_id)->doesntExist();
            })
            ->map(fn (object $record): array => $this->finding(
                code: 'IDEMPOTENCY_RESOURCE_MISSING',
                tenantId: (string) $record->tenant_id,
                outletId: (string) $record->outlet_id,
                resourceType: 'sales_idempotency_record',
                resourceId: (string) $record->id,
                detail: sprintf(
                    'Idempotency record %s:%s points to missing %s %s.',
                    $record->action,
                    $record->idempotency_key,
                    $record->resource_type,
                    $record->resource_id,
                ),
            ))
            ->values()
            ->all();
    }

    /**
     * @return array{code: string, severity: string, tenant_id: string, outlet_id: string|null, resource_type: string, resource_id: string, detail: string}
     */
    private function finding(
        string $code,
        string $tenantId,
        ?string $outletId,
        string $resourceType,
        string $resourceId,
        string $detail,
    ): array {
        return [
            'code' => $code,
            'severity' => 'operator_review',
            'tenant_id' => $tenantId,
            'outlet_id' => $outletId,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'detail' => $detail,
        ];
    }
}
