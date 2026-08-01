<x-tenant.app-layout
    :tenant="$tenant"
    :context="$context"
    title="Stock Card"
    heading="Stock Card"
    description="Review inventory movement ledger and balance projection."
>
    <section class="rounded-lg border border-slate-200 bg-white p-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-black">{{ $item->name }}</h2>
                <p class="text-sm text-slate-600">{{ $item->sku }} · Outlet {{ $outletId }}</p>
            </div>
            <div class="text-right text-sm">
                <p class="font-black">{{ $balance->quantity }}</p>
                <p class="text-slate-600">{{ $balance->currency }} {{ number_format($balance->totalCostMinor) }}</p>
                <p class="text-xs text-slate-500">Average {{ $balance->averageCostMinor === null ? '-' : number_format($balance->averageCostMinor) }}</p>
            </div>
        </div>

        <form class="mt-5 grid gap-3 md:grid-cols-[1fr_1fr_1fr_auto]" method="get">
            <input type="hidden" name="outlet_id" value="{{ $outletId }}">
            <input class="rounded-lg border border-slate-300 px-3 py-2 text-sm" name="from" type="date" value="{{ request('from') }}">
            <input class="rounded-lg border border-slate-300 px-3 py-2 text-sm" name="to" type="date" value="{{ request('to') }}">
            <input class="rounded-lg border border-slate-300 px-3 py-2 text-sm" name="source_type" value="{{ request('source_type') }}" placeholder="Source type">
            <button class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-bold">Filter</button>
        </form>

        <div class="mt-5 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="py-2 pr-4">Time</th>
                        <th class="py-2 pr-4">Type</th>
                        <th class="py-2 pr-4">Source</th>
                        <th class="py-2 pr-4">Qty</th>
                        <th class="py-2 pr-4">Cost</th>
                        <th class="py-2 pr-4">Balance</th>
                        <th class="py-2 pr-4">Reason</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($entries as $entry)
                        <tr>
                            <td class="py-3 pr-4 whitespace-nowrap">{{ $entry->occurredAt->format('Y-m-d H:i') }}</td>
                            <td class="py-3 pr-4">{{ $entry->movementType }}</td>
                            <td class="py-3 pr-4">{{ $entry->sourceType }}</td>
                            <td class="py-3 pr-4 font-bold">{{ $entry->quantity }}</td>
                            <td class="py-3 pr-4">{{ $entry->currency }} {{ number_format($entry->totalCostMinor) }}</td>
                            <td class="py-3 pr-4">{{ $entry->balanceQuantityAfter }} · {{ number_format($entry->balanceTotalCostMinorAfter) }}</td>
                            <td class="py-3 pr-4">{{ $entry->reason ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="py-4 text-slate-600" colspan="7">No stock movement yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-tenant.app-layout>
