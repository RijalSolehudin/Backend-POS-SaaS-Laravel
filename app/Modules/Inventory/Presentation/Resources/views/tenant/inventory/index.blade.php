<x-tenant.app-layout
    :tenant="$tenant"
    :context="$context"
    title="Inventory"
    heading="Inventory"
    description="Manage stock units, inventory items, and outlet stock settings."
>
    <div class="grid gap-8 lg:grid-cols-[0.85fr_1.15fr]">
        <section class="rounded-lg border border-slate-200 bg-white p-5">
            <h2 class="text-lg font-black">Units</h2>
            <form class="mt-4 grid gap-3" method="post" action="{{ route('tenant.inventory.units.store', ['tenant' => $tenant->id]) }}">
                @csrf
                <input class="w-full rounded-lg border border-slate-300 px-3 py-2" name="name" placeholder="Unit name" required maxlength="120">
                <div class="grid grid-cols-[1fr_120px] gap-2">
                    <input class="rounded-lg border border-slate-300 px-3 py-2" name="symbol" placeholder="kg" required maxlength="24">
                    <input class="rounded-lg border border-slate-300 px-3 py-2" name="precision" type="number" min="0" max="3" step="1" value="3" required>
                </div>
                <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-bold text-white">Create unit</button>
            </form>

            <div class="mt-5 space-y-3">
                @forelse ($units as $unit)
                    <div class="rounded-lg border border-slate-200 p-4">
                        <form class="grid gap-3" method="post" action="{{ route('tenant.inventory.units.update', ['tenant' => $tenant->id, 'unit' => $unit->id]) }}">
                            @csrf
                            @method('put')
                            <input class="w-full rounded-lg border border-slate-300 px-3 py-2" name="name" value="{{ $unit->name }}" required maxlength="120">
                            <div class="grid grid-cols-[1fr_120px] gap-2">
                                <input class="rounded-lg border border-slate-300 px-3 py-2" name="symbol" value="{{ $unit->symbol }}" required maxlength="24">
                                <input class="rounded-lg border border-slate-300 px-3 py-2" name="precision" type="number" min="0" max="3" step="1" value="{{ $unit->precision }}" required>
                            </div>
                            <button class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold">Save unit</button>
                        </form>
                        <form class="mt-3" method="post" action="{{ route('tenant.inventory.units.status', ['tenant' => $tenant->id, 'unit' => $unit->id]) }}">
                            @csrf
                            <input type="hidden" name="status" value="{{ $unit->status->value === 'active' ? 'inactive' : 'active' }}">
                            <button class="text-sm font-bold text-slate-600">
                                {{ $unit->status->value === 'active' ? 'Deactivate unit' : 'Activate unit' }}
                            </button>
                            <span class="ml-2 text-xs uppercase text-slate-500">{{ $unit->status->value }}</span>
                        </form>
                    </div>
                @empty
                    <p class="rounded-lg bg-slate-50 p-4 text-sm text-slate-600">No inventory units yet.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-5">
            <h2 class="text-lg font-black">Items</h2>
            <form class="mt-4 grid gap-3 md:grid-cols-2" method="post" action="{{ route('tenant.inventory.items.store', ['tenant' => $tenant->id]) }}">
                @csrf
                <input class="rounded-lg border border-slate-300 px-3 py-2" name="name" placeholder="Item name" required maxlength="160">
                <input class="rounded-lg border border-slate-300 px-3 py-2" name="sku" placeholder="SKU" required maxlength="64">
                <select class="rounded-lg border border-slate-300 px-3 py-2 md:col-span-2" name="unit_id" required>
                    <option value="">Choose base unit</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->symbol }})</option>
                    @endforeach
                </select>
                <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-bold text-white md:col-span-2">Create item</button>
            </form>

            <div class="mt-6 space-y-5">
                @forelse ($items as $item)
                    <article class="rounded-lg border border-slate-200 p-4">
                        <form class="grid gap-3 md:grid-cols-2" method="post" action="{{ route('tenant.inventory.items.update', ['tenant' => $tenant->id, 'item' => $item->id]) }}">
                            @csrf
                            @method('put')
                            <input class="rounded-lg border border-slate-300 px-3 py-2" name="name" value="{{ $item->name }}" required maxlength="160">
                            <input class="rounded-lg border border-slate-300 px-3 py-2" name="sku" value="{{ $item->sku }}" required maxlength="64">
                            <select class="rounded-lg border border-slate-300 px-3 py-2 md:col-span-2" name="unit_id" required>
                                @foreach ($units as $unit)
                                    <option value="{{ $unit->id }}" @selected($item->unit_id === $unit->id)>{{ $unit->name }} ({{ $unit->symbol }})</option>
                                @endforeach
                            </select>
                            <button class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold md:col-span-2">Save item</button>
                        </form>

                        <form class="mt-3" method="post" action="{{ route('tenant.inventory.items.status', ['tenant' => $tenant->id, 'item' => $item->id]) }}">
                            @csrf
                            <input type="hidden" name="status" value="{{ $item->status->value === 'active' ? 'inactive' : 'active' }}">
                            <button class="text-sm font-bold text-slate-600">{{ $item->status->value === 'active' ? 'Deactivate item' : 'Activate item' }}</button>
                            <span class="ml-2 text-xs uppercase text-slate-500">{{ $item->status->value }}</span>
                        </form>

                        <div class="mt-4 border-t border-slate-200 pt-4">
                            <p class="text-sm font-black">Outlet settings</p>
                            <div class="mt-3 grid gap-3">
                                @foreach ($outlets as $outlet)
                                    @php($setting = $settings->get($item->id.'|'.$outlet->outletId))
                                    <form class="grid gap-2 rounded-lg bg-slate-50 p-3 md:grid-cols-[1fr_120px_140px_auto]" method="post" action="{{ route('tenant.inventory.items.outlet-settings', ['tenant' => $tenant->id, 'item' => $item->id]) }}">
                                        @csrf
                                        @method('put')
                                        <input type="hidden" name="outlet_id" value="{{ $outlet->outletId }}">
                                        <p class="text-sm font-bold">{{ $outlet->name }}</p>
                                        <select class="rounded-lg border border-slate-300 px-3 py-2 text-sm" name="status">
                                            <option value="active" @selected(($setting?->status->value ?? 'active') === 'active')>Active</option>
                                            <option value="inactive" @selected(($setting?->status->value ?? 'active') === 'inactive')>Inactive</option>
                                        </select>
                                        <input class="rounded-lg border border-slate-300 px-3 py-2 text-sm" name="low_stock_threshold_quantity" inputmode="decimal" value="{{ $setting?->low_stock_threshold_quantity ?? '0.000' }}" required>
                                        <button class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-bold">Save</button>
                                    </form>
                                @endforeach
                            </div>
                        </div>
                    </article>
                @empty
                    <p class="rounded-lg bg-slate-50 p-4 text-sm text-slate-600">No inventory items yet.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-tenant.app-layout>
