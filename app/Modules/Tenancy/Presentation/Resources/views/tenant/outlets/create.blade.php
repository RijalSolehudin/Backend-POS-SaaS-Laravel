<x-tenant.app-layout :tenant="$tenant" :context="$context" title="Create outlet" heading="Create outlet" description="Codes are unique within this tenant and normalized to uppercase.">
    <form method="post" action="{{ route('tenant.outlets.store', ['tenant' => $tenant->id]) }}" class="max-w-xl space-y-5 rounded-xl border border-slate-200 bg-white p-6">
        @csrf
        <div><label class="block text-sm font-bold" for="name">Outlet name</label><input class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2" id="name" name="name" value="{{ old('name') }}" maxlength="120" required></div>
        <div><label class="block text-sm font-bold" for="code">Outlet code</label><input class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 uppercase" id="code" name="code" value="{{ old('code') }}" maxlength="32" required></div>
        <button class="rounded-lg bg-emerald-700 px-4 py-2.5 font-bold text-white">Create outlet</button>
    </form>
</x-tenant.app-layout>
