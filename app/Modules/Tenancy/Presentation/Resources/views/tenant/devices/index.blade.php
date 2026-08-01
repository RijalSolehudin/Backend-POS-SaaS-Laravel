<x-tenant.app-layout :tenant="$tenant" :context="$context" :can-manage-devices="true" title="POS devices" heading="POS devices" description="Register, move, and revoke POS terminals bound to one outlet.">
    @php($outletNames = collect($outlets)->keyBy('id'))
    <form method="post" action="{{ route('tenant.devices.store', ['tenant' => $tenant->id]) }}" class="mb-6 grid gap-4 rounded-xl border border-slate-200 bg-white p-6 md:grid-cols-5">
        @csrf
        <input class="rounded-lg border border-slate-300 px-3 py-2 md:col-span-2" name="installation_id" placeholder="Installation ULID" maxlength="26" required>
        <input class="rounded-lg border border-slate-300 px-3 py-2" name="name" placeholder="Device name" maxlength="120" required>
        <select class="rounded-lg border border-slate-300 px-3 py-2" name="outlet_id" required>
            <option value="">Outlet</option>
            @foreach ($outlets as $outlet)
                <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <input class="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2" name="platform" placeholder="android" maxlength="40" required>
            <input class="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2" name="app_version" placeholder="1.0.0" maxlength="40">
        </div>
        <button class="rounded-lg bg-emerald-700 px-4 py-2.5 font-bold text-white md:col-span-5">Register device</button>
    </form>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr><th class="px-5 py-3">Device</th><th class="px-5 py-3">Installation</th><th class="px-5 py-3">Outlet</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Actions</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($devices as $device)
                    <tr>
                        <td class="px-5 py-4"><p class="font-bold">{{ $device->name }}</p><p class="text-xs text-slate-500">{{ $device->platform }}{{ $device->app_version ? ' · '.$device->app_version : '' }}</p></td>
                        <td class="px-5 py-4 font-mono text-xs">{{ $device->installation_id }}</td>
                        <td class="px-5 py-4">{{ $outletNames->get($device->outlet_id)?->name ?? $device->outlet_id }}</td>
                        <td class="px-5 py-4">{{ ucfirst($device->status->value) }}</td>
                        <td class="px-5 py-4">
                            <div class="flex flex-col gap-3">
                                <form method="post" action="{{ route('tenant.devices.reassign', ['tenant' => $tenant->id, 'device' => $device->id]) }}" class="flex flex-wrap gap-2">
                                    @csrf @method('put')
                                    <select class="rounded-lg border border-slate-300 px-3 py-2" name="outlet_id" required>
                                        @foreach ($outlets as $outlet)
                                            <option value="{{ $outlet->id }}" @selected($device->outlet_id === $outlet->id)>{{ $outlet->name }}</option>
                                        @endforeach
                                    </select>
                                    <input class="min-w-48 rounded-lg border border-slate-300 px-3 py-2" name="reason" placeholder="Reason" minlength="10" maxlength="500" required>
                                    <button class="rounded-lg border border-slate-300 px-4 py-2 font-bold">Move</button>
                                </form>
                                @if ($device->status->value === 'active')
                                    <form method="post" action="{{ route('tenant.devices.revoke', ['tenant' => $tenant->id, 'device' => $device->id]) }}" class="flex flex-wrap gap-2">
                                        @csrf
                                        <input class="min-w-48 rounded-lg border border-rose-300 px-3 py-2" name="reason" placeholder="Reason" minlength="10" maxlength="500" required>
                                        <button class="rounded-lg bg-rose-700 px-4 py-2 font-bold text-white">Revoke</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">No POS devices registered.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-tenant.app-layout>
