<x-tenant.app-layout
    :tenant="$tenant"
    :context="$context"
    title="Procurement"
    heading="Procurement"
    description="Manage suppliers and supplier item mappings."
>
    <div class="space-y-6">
            @if (session('status'))
                <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif

            <section class="grid gap-6 lg:grid-cols-2">
                <form method="POST" action="{{ route('tenant.procurement.suppliers.store', ['tenant' => $tenant->id]) }}" class="space-y-4">
                    @csrf
                    <h3 class="text-base font-semibold text-gray-900">Supplier</h3>
                    <input class="w-full rounded-md border-gray-300" name="name" placeholder="Name" required maxlength="160">
                    <input class="w-full rounded-md border-gray-300" name="code" placeholder="Code" required maxlength="64">
                    <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white">Save</button>
                </form>

                <form method="POST" action="{{ route('tenant.procurement.supplier-items.store', ['tenant' => $tenant->id]) }}" class="space-y-4">
                    @csrf
                    <h3 class="text-base font-semibold text-gray-900">Supplier Item</h3>
                    <select class="w-full rounded-md border-gray-300" name="supplier_id" required>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                    <select class="w-full rounded-md border-gray-300" name="inventory_item_id" required>
                        @foreach ($inventoryItems as $item)
                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                    <input class="w-full rounded-md border-gray-300" name="supplier_sku" placeholder="Supplier SKU" required maxlength="80">
                    <input class="w-full rounded-md border-gray-300" name="last_price_minor" type="number" min="0" placeholder="Last price minor" required>
                    <input class="w-full rounded-md border-gray-300" name="currency" value="{{ $tenant->currency }}" required maxlength="3">
                    <label class="flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" name="is_active" value="1" checked> Active</label>
                    <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white">Save</button>
                </form>
            </section>

            <section class="space-y-3">
                <h3 class="text-base font-semibold text-gray-900">Suppliers</h3>
                <div class="overflow-hidden rounded-md border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-gray-600">
                            <tr><th class="px-4 py-2">Code</th><th class="px-4 py-2">Name</th><th class="px-4 py-2">Status</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($suppliers as $supplier)
                                <tr><td class="px-4 py-2">{{ $supplier->code }}</td><td class="px-4 py-2">{{ $supplier->name }}</td><td class="px-4 py-2">{{ $supplier->status->value }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
    </div>
</x-tenant.app-layout>
