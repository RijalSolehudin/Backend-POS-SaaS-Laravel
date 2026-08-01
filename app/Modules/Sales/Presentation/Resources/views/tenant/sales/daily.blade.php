<x-tenant.app-layout
    :tenant="$tenant"
    title="Daily Sales"
    heading="Daily Sales"
    description="Review completed orders and recorded payments by business date."
>
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-slate-600">{{ $tenant->name }} · {{ $summary->businessDate }}</p>
            <form method="GET" action="{{ route('tenant.sales.daily', ['tenant' => $tenant->id]) }}" class="flex items-center gap-2">
                <input
                    type="date"
                    name="date"
                    value="{{ $summary->businessDate }}"
                    class="rounded-lg border border-slate-300 px-3 py-2 text-sm"
                >
                <button class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-bold text-white">Apply</button>
            </form>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <p class="text-sm text-slate-500">Completed Orders</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $summary->completedOrdersCount }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <p class="text-sm text-slate-500">Gross Sales</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($summary->grossSalesMinor, 2) }} {{ $summary->currency }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <p class="text-sm text-slate-500">Refunds</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($summary->refundsMinor, 2) }} {{ $summary->currency }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <p class="text-sm text-slate-500">Net Sales</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($summary->netSalesMinor, 2) }} {{ $summary->currency }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <p class="text-sm text-slate-500">Cash Payments</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($summary->cashPaymentsMinor, 2) }} {{ $summary->currency }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <p class="text-sm text-slate-500">Manual Non-Cash</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($summary->manualNonCashPaymentsMinor, 2) }} {{ $summary->currency }}</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Outlet</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-slate-500">Orders</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-slate-500">Gross</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-slate-500">Refunds</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-slate-500">Net</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-slate-500">Cash</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-slate-500">Manual Non-Cash</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($summary->outlets as $outlet)
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $outlet['outlet_name'] }}</td>
                            <td class="px-4 py-3 text-right text-sm text-slate-700">{{ $outlet['completed_orders_count'] }}</td>
                            <td class="px-4 py-3 text-right text-sm text-slate-700">{{ number_format($outlet['gross_sales_minor'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-slate-700">{{ number_format($outlet['refunds_minor'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-slate-700">{{ number_format($outlet['net_sales_minor'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-slate-700">{{ number_format($outlet['cash_payments_minor'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-slate-700">{{ number_format($outlet['manual_non_cash_payments_minor'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">No active outlets found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-tenant.app-layout>
