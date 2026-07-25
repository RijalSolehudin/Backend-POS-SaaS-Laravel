<x-tenant.app-layout :tenant="$tenant" :context="$context" title="Outlets" heading="Outlets" description="Outlet records and lifecycle are always scoped to this tenant.">
    <div class="mb-6 flex justify-end">
        <a class="rounded-lg bg-emerald-700 px-4 py-2.5 font-bold text-white" href="{{ route('tenant.outlets.create', ['tenant' => $tenant->id]) }}">Create outlet</a>
    </div>
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-3">Outlet</th><th class="px-5 py-3">Code</th><th class="px-5 py-3">Status</th><th class="px-5 py-3"></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse ($outlets as $outlet)
                <tr @class(['bg-emerald-50/60' => $preferredOutletId === $outlet->id])><td class="px-5 py-4 font-bold">{{ $outlet->name }}</td><td class="px-5 py-4">{{ $outlet->code }}</td><td class="px-5 py-4">{{ ucfirst($outlet->status->value) }}</td><td class="px-5 py-4 text-right"><a class="font-bold text-emerald-700" href="{{ route('tenant.outlets.edit', ['tenant' => $tenant->id, 'outlet' => $outlet->id]) }}">Manage</a></td></tr>
            @empty
                <tr><td colspan="4" class="px-5 py-10 text-center text-slate-500">No outlets available.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-tenant.app-layout>
