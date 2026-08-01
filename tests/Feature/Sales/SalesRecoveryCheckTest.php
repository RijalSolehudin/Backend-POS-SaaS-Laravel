<?php

declare(strict_types=1);

namespace Tests\Feature\Sales;

use App\Modules\Identity\Domain\Enums\PredefinedRole;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Identity\Domain\Models\UserRoleAssignment;
use App\Modules\Sales\Domain\Enums\CashMovementType;
use App\Modules\Sales\Domain\Enums\OrderStatus;
use App\Modules\Sales\Domain\Enums\PaymentMethod;
use App\Modules\Sales\Domain\Enums\PaymentStatus;
use App\Modules\Sales\Domain\Enums\SensitiveActionApprovalStatus;
use App\Modules\Sales\Domain\Enums\ShiftStatus;
use App\Modules\Sales\Domain\Models\CashMovement;
use App\Modules\Sales\Domain\Models\IdempotencyRecord;
use App\Modules\Sales\Domain\Models\Order;
use App\Modules\Sales\Domain\Models\Payment;
use App\Modules\Sales\Domain\Models\Receipt;
use App\Modules\Sales\Domain\Models\Refund;
use App\Modules\Sales\Domain\Models\SensitiveActionApproval;
use App\Modules\Sales\Domain\Models\Shift;
use App\Modules\Tenancy\Domain\Enums\MembershipType;
use App\Modules\Tenancy\Domain\Enums\OutletStatus;
use App\Modules\Tenancy\Domain\Enums\TenantStatus;
use App\Modules\Tenancy\Domain\Models\Outlet;
use App\Modules\Tenancy\Domain\Models\Tenant;
use App\Modules\Tenancy\Domain\Models\TenantMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Tests\TestCase;

final class SalesRecoveryCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_recovery_check_passes_when_financial_state_is_consistent(): void
    {
        $context = $this->salesContext();
        $order = $this->order($context, OrderStatus::Completed);
        $payment = $this->payment($context, $order, PaymentStatus::Recorded);
        $this->receipt($context, $order, $payment);

        self::assertSame(0, Artisan::call('sales:recovery-check', ['--json' => true]));

        $payload = $this->jsonOutput();

        self::assertSame(0, $payload['findings_count']);
        self::assertSame([], $payload['findings']);
    }

    public function test_recovery_check_reports_ambiguous_financial_states(): void
    {
        $context = $this->salesContext();

        $completedWithoutPayment = $this->order($context, OrderStatus::Completed, 'REC-0001');
        $draftWithPayment = $this->order($context, OrderStatus::Draft, 'REC-0002');
        $this->payment($context, $draftWithPayment, PaymentStatus::Recorded);

        CashMovement::query()->create([
            'tenant_id' => $context['tenant']->id,
            'outlet_id' => $context['outlet']->id,
            'shift_id' => $context['shift']->id,
            'user_id' => $context['user']->id,
            'type' => CashMovementType::CashOut,
            'amount_minor' => 25000,
            'currency' => 'IDR',
            'reason' => 'Emergency cash out',
            'recorded_at' => now(),
        ]);

        $approval = SensitiveActionApproval::query()->create([
            'tenant_id' => $context['tenant']->id,
            'outlet_id' => $context['outlet']->id,
            'performer_user_id' => $context['user']->id,
            'approver_user_id' => $context['user']->id,
            'action' => 'payments.refund',
            'target_type' => 'sales_order',
            'target_id' => $completedWithoutPayment->id,
            'request_fingerprint' => hash('sha256', 'refund'),
            'status' => SensitiveActionApprovalStatus::Approved,
            'reason' => 'Refund approved but not consumed',
            'decision_reason' => 'Approved for test',
            'requested_at' => now(),
            'approved_at' => now(),
            'expires_at' => now()->addMinutes(10),
        ]);

        $payment = $this->payment($context, $completedWithoutPayment, PaymentStatus::Recorded);

        Refund::query()->create([
            'tenant_id' => $context['tenant']->id,
            'outlet_id' => $context['outlet']->id,
            'shift_id' => $context['shift']->id,
            'order_id' => $completedWithoutPayment->id,
            'payment_id' => $payment->id,
            'approval_id' => $approval->id,
            'refunded_by' => $context['user']->id,
            'method' => PaymentMethod::Cash,
            'status' => 'recorded',
            'amount_minor' => 10000,
            'currency' => 'IDR',
            'reason' => 'Manual refund',
            'recorded_at' => now(),
        ]);

        IdempotencyRecord::query()->create([
            'tenant_id' => $context['tenant']->id,
            'outlet_id' => $context['outlet']->id,
            'user_id' => $context['user']->id,
            'action' => 'payments.refund',
            'idempotency_key' => 'missing-resource-key',
            'request_hash' => hash('sha256', 'missing-resource'),
            'resource_type' => 'sales_refund',
            'resource_id' => strtolower((string) Str::ulid()),
            'response_status' => 201,
            'response_body' => ['id' => 'missing'],
            'expires_at' => now()->addDay(),
        ]);

        self::assertSame(1, Artisan::call('sales:recovery-check', ['--json' => true]));

        $payload = $this->jsonOutput();
        $codes = collect($payload['findings'])->pluck('code')->all();

        self::assertContains('ORDER_COMPLETED_WITHOUT_RECEIPT', $codes);
        self::assertContains('PAYMENT_RECORDED_FOR_NON_COMPLETED_ORDER', $codes);
        self::assertContains('CASH_OUT_APPROVAL_NOT_CONSUMED', $codes);
        self::assertContains('REFUND_APPROVAL_NOT_CONSUMED', $codes);
        self::assertContains('IDEMPOTENCY_RESOURCE_MISSING', $codes);
    }

    /**
     * @return array{tenant: Tenant, outlet: Outlet, user: User, shift: Shift}
     */
    private function salesContext(): array
    {
        $tenant = Tenant::query()->create([
            'name' => 'Tenant One',
            'code' => 'tenant-one',
            'currency' => 'IDR',
            'timezone' => 'Asia/Jakarta',
            'status' => TenantStatus::Active,
        ]);
        $user = User::factory()->create(['email' => sprintf('owner-%s@example.com', Str::lower(Str::random(6)))]);

        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'membership_type' => MembershipType::Owner,
        ]);
        UserRoleAssignment::query()->create([
            'user_id' => $user->id,
            'role' => PredefinedRole::TenantOwner,
        ]);

        $outlet = Outlet::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Main Outlet',
            'code' => 'MAIN',
            'status' => OutletStatus::Active,
        ]);

        $shift = Shift::query()->create([
            'tenant_id' => $tenant->id,
            'outlet_id' => $outlet->id,
            'user_id' => $user->id,
            'open_shift_key' => sprintf('%s:%s:open', $outlet->id, $user->id),
            'status' => ShiftStatus::Open,
            'opened_at' => now(),
            'opening_cash_minor' => 100000,
            'expected_cash_minor' => 100000,
            'gross_sales_minor' => 0,
            'currency' => 'IDR',
        ]);

        return [
            'tenant' => $tenant,
            'outlet' => $outlet,
            'user' => $user,
            'shift' => $shift,
        ];
    }

    /**
     * @param  array{tenant: Tenant, outlet: Outlet, user: User, shift: Shift}  $context
     */
    private function order(array $context, OrderStatus $status, ?string $number = null): Order
    {
        return Order::query()->create([
            'tenant_id' => $context['tenant']->id,
            'outlet_id' => $context['outlet']->id,
            'shift_id' => $context['shift']->id,
            'user_id' => $context['user']->id,
            'order_number' => $number ?? sprintf('REC-%s', Str::upper(Str::random(6))),
            'status' => $status,
            'subtotal_minor' => 10000,
            'discount_minor' => 0,
            'service_charge_minor' => 0,
            'tax_minor' => 0,
            'total_minor' => 10000,
            'currency' => 'IDR',
            'completed_at' => $status === OrderStatus::Completed ? now() : null,
        ]);
    }

    /**
     * @param  array{tenant: Tenant, outlet: Outlet, user: User, shift: Shift}  $context
     */
    private function payment(array $context, Order $order, PaymentStatus $status): Payment
    {
        return Payment::query()->create([
            'tenant_id' => $context['tenant']->id,
            'outlet_id' => $context['outlet']->id,
            'shift_id' => $context['shift']->id,
            'order_id' => $order->id,
            'method' => PaymentMethod::Cash,
            'status' => $status,
            'amount_minor' => 10000,
            'currency' => 'IDR',
            'recorded_at' => now(),
        ]);
    }

    /**
     * @param  array{tenant: Tenant, outlet: Outlet, user: User, shift: Shift}  $context
     */
    private function receipt(array $context, Order $order, Payment $payment): Receipt
    {
        return Receipt::query()->create([
            'tenant_id' => $context['tenant']->id,
            'outlet_id' => $context['outlet']->id,
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'receipt_number' => sprintf('R-%s', Str::upper(Str::random(6))),
            'issued_at' => now(),
            'snapshot' => [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'total_minor' => 10000,
                'currency' => 'IDR',
            ],
        ]);
    }

    /**
     * @return array{findings_count: int, findings: array<int, array{code: string}>}
     */
    private function jsonOutput(): array
    {
        $decoded = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($decoded);

        $findingsCount = $decoded['findings_count'] ?? null;
        self::assertIsInt($findingsCount);

        $rawFindings = $decoded['findings'] ?? null;
        self::assertIsArray($rawFindings);

        $findings = [];

        foreach ($rawFindings as $finding) {
            self::assertIsArray($finding);

            $code = $finding['code'] ?? null;
            self::assertIsString($code);

            $findings[] = ['code' => $code];
        }

        return [
            'findings_count' => $findingsCount,
            'findings' => $findings,
        ];
    }
}
