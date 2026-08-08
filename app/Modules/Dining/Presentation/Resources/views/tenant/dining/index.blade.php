<x-tenant.app-layout
    :tenant="$tenant"
    :context="$context"
    title="Dining"
    heading="Dining"
    description="Manage outlet floors and dine-in tables."
>
    @php($floorsByOutlet = $floors->groupBy('outlet_id'))
    @php($tablesByOutlet = $tables->groupBy('outlet_id'))

    <div class="grid gap-8 lg:grid-cols-[0.9fr_1.1fr]">
        <section class="rounded-lg border border-slate-200 bg-white p-5">
            <h2 class="text-lg font-black">Floors</h2>
            <form class="mt-4 grid gap-3" method="post" action="{{ route('tenant.dining.floors.store', ['tenant' => $tenant->id]) }}">
                @csrf
                <select class="rounded-lg border border-slate-300 px-3 py-2" name="outlet_id" required>
                    <option value="">Choose outlet</option>
                    @foreach ($outlets as $outlet)
                        <option value="{{ $outlet->outletId }}">{{ $outlet->name }}</option>
                    @endforeach
                </select>
                <input class="rounded-lg border border-slate-300 px-3 py-2" name="name" placeholder="Floor name" required maxlength="120">
                <div class="grid grid-cols-[1fr_130px] gap-2">
                    <input class="rounded-lg border border-slate-300 px-3 py-2" name="code" placeholder="MAIN" required maxlength="32">
                    <input class="rounded-lg border border-slate-300 px-3 py-2" name="display_order" type="number" min="0" step="1" value="0" required>
                </div>
                <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-bold text-white">Create floor</button>
            </form>

            <div class="mt-5 space-y-5">
                @forelse ($outlets as $outlet)
                    <div>
                        <h3 class="text-sm font-black uppercase text-slate-500">{{ $outlet->name }}</h3>
                        <div class="mt-2 space-y-3">
                            @forelse ($floorsByOutlet->get($outlet->outletId, collect()) as $floor)
                                <article class="rounded-lg border border-slate-200 p-4">
                                    <form class="grid gap-3" method="post" action="{{ route('tenant.dining.floors.update', ['tenant' => $tenant->id, 'floor' => $floor->id]) }}">
                                        @csrf
                                        @method('put')
                                        <input type="hidden" name="outlet_id" value="{{ $floor->outlet_id }}">
                                        <input class="rounded-lg border border-slate-300 px-3 py-2" name="name" value="{{ $floor->name }}" required maxlength="120">
                                        <div class="grid grid-cols-[1fr_130px] gap-2">
                                            <input class="rounded-lg border border-slate-300 px-3 py-2" name="code" value="{{ $floor->code }}" required maxlength="32">
                                            <input class="rounded-lg border border-slate-300 px-3 py-2" name="display_order" type="number" min="0" step="1" value="{{ $floor->display_order }}" required>
                                        </div>
                                        <button class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold">Save floor</button>
                                    </form>
                                    <form class="mt-3" method="post" action="{{ route('tenant.dining.floors.status', ['tenant' => $tenant->id, 'floor' => $floor->id]) }}">
                                        @csrf
                                        <input type="hidden" name="status" value="{{ $floor->status->value === 'active' ? 'inactive' : 'active' }}">
                                        <button class="text-sm font-bold text-slate-600">
                                            {{ $floor->status->value === 'active' ? 'Deactivate floor' : 'Activate floor' }}
                                        </button>
                                        <span class="ml-2 text-xs uppercase text-slate-500">{{ $floor->status->value }}</span>
                                    </form>
                                </article>
                            @empty
                                <p class="rounded-lg bg-slate-50 p-4 text-sm text-slate-600">No dining floors yet.</p>
                            @endforelse
                        </div>
                    </div>
                @empty
                    <p class="rounded-lg bg-slate-50 p-4 text-sm text-slate-600">Create an outlet before adding dining floors.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-5">
            <h2 class="text-lg font-black">Tables</h2>
            <form class="mt-4 grid gap-3 md:grid-cols-2" method="post" action="{{ route('tenant.dining.tables.store', ['tenant' => $tenant->id]) }}">
                @csrf
                <select class="rounded-lg border border-slate-300 px-3 py-2" name="outlet_id" required>
                    <option value="">Choose outlet</option>
                    @foreach ($outlets as $outlet)
                        <option value="{{ $outlet->outletId }}">{{ $outlet->name }}</option>
                    @endforeach
                </select>
                <select class="rounded-lg border border-slate-300 px-3 py-2" name="floor_id" required>
                    <option value="">Choose floor</option>
                    @foreach ($floors as $floor)
                        <option value="{{ $floor->id }}">{{ $floor->name }} · {{ $floor->code }}</option>
                    @endforeach
                </select>
                <input class="rounded-lg border border-slate-300 px-3 py-2" name="name" placeholder="Table name" required maxlength="120">
                <input class="rounded-lg border border-slate-300 px-3 py-2" name="code" placeholder="T01" required maxlength="32">
                <input class="rounded-lg border border-slate-300 px-3 py-2" name="capacity" type="number" min="1" max="999" step="1" value="2" required>
                <input class="rounded-lg border border-slate-300 px-3 py-2" name="display_order" type="number" min="0" step="1" value="0" required>
                <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-bold text-white md:col-span-2">Create table</button>
            </form>

            <div class="mt-5 space-y-5">
                @forelse ($outlets as $outlet)
                    <div>
                        <h3 class="text-sm font-black uppercase text-slate-500">{{ $outlet->name }}</h3>
                        <div class="mt-2 space-y-3">
                            @forelse ($tablesByOutlet->get($outlet->outletId, collect()) as $table)
                                @php($outletFloors = $floorsByOutlet->get($table->outlet_id, collect()))
                                <article class="rounded-lg border border-slate-200 p-4">
                                    <form class="grid gap-3 md:grid-cols-2" method="post" action="{{ route('tenant.dining.tables.update', ['tenant' => $tenant->id, 'table' => $table->id]) }}">
                                        @csrf
                                        @method('put')
                                        <input type="hidden" name="outlet_id" value="{{ $table->outlet_id }}">
                                        <select class="rounded-lg border border-slate-300 px-3 py-2 md:col-span-2" name="floor_id" required>
                                            @foreach ($outletFloors as $floor)
                                                <option value="{{ $floor->id }}" @selected($table->floor_id === $floor->id)>{{ $floor->name }} · {{ $floor->code }}</option>
                                            @endforeach
                                        </select>
                                        <input class="rounded-lg border border-slate-300 px-3 py-2" name="name" value="{{ $table->name }}" required maxlength="120">
                                        <input class="rounded-lg border border-slate-300 px-3 py-2" name="code" value="{{ $table->code }}" required maxlength="32">
                                        <input class="rounded-lg border border-slate-300 px-3 py-2" name="capacity" type="number" min="1" max="999" step="1" value="{{ $table->capacity }}" required>
                                        <input class="rounded-lg border border-slate-300 px-3 py-2" name="display_order" type="number" min="0" step="1" value="{{ $table->display_order }}" required>
                                        <button class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold md:col-span-2">Save table</button>
                                    </form>
                                    <form class="mt-3" method="post" action="{{ route('tenant.dining.tables.status', ['tenant' => $tenant->id, 'table' => $table->id]) }}">
                                        @csrf
                                        <input type="hidden" name="status" value="{{ $table->status->value === 'active' ? 'inactive' : 'active' }}">
                                        <button class="text-sm font-bold text-slate-600">
                                            {{ $table->status->value === 'active' ? 'Deactivate table' : 'Activate table' }}
                                        </button>
                                        <span class="ml-2 text-xs uppercase text-slate-500">{{ $table->status->value }} · seats {{ $table->capacity }}</span>
                                    </form>
                                </article>
                            @empty
                                <p class="rounded-lg bg-slate-50 p-4 text-sm text-slate-600">No dining tables yet.</p>
                            @endforelse
                        </div>
                    </div>
                @empty
                    <p class="rounded-lg bg-slate-50 p-4 text-sm text-slate-600">Create an outlet and floor before adding tables.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-tenant.app-layout>
