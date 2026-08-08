<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Application\Actions;

use App\Modules\Reporting\Domain\Enums\AnalyticsExportStatus;
use App\Modules\Reporting\Domain\Models\AnalyticsExport;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class CreateAnalyticsExport
{
    public function __construct(private TenantPermissionGuard $permissions) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function handle(TenantRequestContext $context, string $exportType, array $filters = [], ?string $outletId = null): AnalyticsExport
    {
        $this->permissions->authorizeManageCatalog($context);

        $export = AnalyticsExport::query()->create([
            'tenant_id' => $context->tenantId,
            'outlet_id' => $outletId,
            'requested_by' => $context->userId,
            'export_type' => $exportType,
            'status' => AnalyticsExportStatus::Pending,
            'filters' => $this->redact($filters),
        ]);

        $result = match ($exportType) {
            'growth_summary' => $this->growthSummary($context->tenantId, $outletId),
            default => ['message' => 'Export type has no configured rows yet.'],
        };

        $export->forceFill([
            'status' => AnalyticsExportStatus::Completed,
            'result' => $result,
            'completed_at' => CarbonImmutable::now(),
        ])->save();

        return $export;
    }

    /**
     * @return array<string, int|string|null>
     */
    private function growthSummary(string $tenantId, ?string $outletId): array
    {
        $orders = DB::table('sales_orders')->where('tenant_id', $tenantId);
        $requests = DB::table('ordering_order_requests')->where('tenant_id', $tenantId);
        $intents = DB::table('payment_gateway_intents')->where('tenant_id', $tenantId);
        $reservations = DB::table('reservations')->where('tenant_id', $tenantId);

        if ($outletId !== null) {
            $orders->where('outlet_id', $outletId);
            $requests->where('outlet_id', $outletId);
            $intents->where('outlet_id', $outletId);
            $reservations->where('outlet_id', $outletId);
        }

        return [
            'tenant_id' => $tenantId,
            'outlet_id' => $outletId,
            'orders_count' => (int) $orders->count(),
            'gross_sales_minor' => (int) $orders->sum('total_minor'),
            'order_requests_count' => (int) $requests->count(),
            'gateway_intents_count' => (int) $intents->count(),
            'reservations_count' => (int) $reservations->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function redact(array $payload): array
    {
        unset($payload['card'], $payload['card_number'], $payload['cvv'], $payload['provider_payload']);

        if (array_key_exists('customer_phone', $payload)) {
            $payload['customer_phone'] = '[redacted]';
        }

        return $payload;
    }
}
