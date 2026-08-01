<?php

declare(strict_types=1);

namespace App\Modules\Sales\Presentation\Http\Web\Controllers;

use App\Modules\Sales\Application\Actions\SummarizeDailySales;
use App\Modules\Tenancy\Application\Contracts\TenantCatalogReference;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class TenantSalesSummaryController extends Controller
{
    public function __invoke(
        Request $request,
        SummarizeDailySales $summaries,
        TenantCatalogReference $tenancy,
    ): View {
        $tenantId = (string) $request->route('tenant');
        /** @var array{date?: string|null} $validated */
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $businessDate = $validated['date'] ?? CarbonImmutable::today()->toDateString();
        $tenant = $tenancy->tenant($tenantId);
        abort_if($tenant === null, 404);

        return view('sales::tenant.sales.daily', [
            'tenant' => (object) ['id' => $tenant->tenantId, 'name' => $tenant->name],
            'summary' => $summaries->handle($tenantId, $businessDate),
        ]);
    }
}
