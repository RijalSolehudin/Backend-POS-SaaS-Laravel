<x-tenant.app-layout
    :tenant="$tenant"
    :context="$context"
    title="Low Stock"
    heading="Low Stock"
    description="Review inventory items at or below outlet minimum threshold."
>
    <section class="rounded-lg border border-slate-200 bg-white p-5">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-black">Outlet {{ $outletId }}</h2>
                <p class="text-sm text-slate-600">Items at or below configured threshold.</p>
            </div>
            <a class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-bold" href="{{ route('tenant.inventory.index', ['tenant' => $tenant->id]) }}">Inventory</a>
        </div>

        <div class="mt-5 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="py-2 pr-4">Item</th>
                        <th class="py-2 pr-4">SKU</th>
                        <th class="py-2 pr-4">Qty</th>
                        <th class="py-2 pr-4">Threshold</th>
                        <th class="py-2 pr-4">Valuation</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($items as $item)
                        <tr>
                            <td class="py-3 pr-4 font-bold">{{ $item->itemName }}</td>
                            <td class="py-3 pr-4">{{ $item->sku }}</td>
                            <td class="py-3 pr-4">{{ $item->quantity }} {{ $item->unitSymbol }}</td>
                            <td class="py-3 pr-4">{{ $item->thresholdQuantity }} {{ $item->unitSymbol }}</td>
                            <td class="py-3 pr-4">{{ $item->currency }} {{ number_format($item->totalCostMinor) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="py-4 text-slate-600" colspan="5">No low-stock items.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-tenant.app-layout>
