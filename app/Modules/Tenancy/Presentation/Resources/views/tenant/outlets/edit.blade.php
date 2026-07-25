<x-tenant.app-layout :tenant="$tenant" :context="$context" title="Manage outlet" :heading="$outlet->name" description="Update this outlet and control which tenant users may operate here.">
    <div class="grid gap-6 lg:grid-cols-2">
        <form method="post" action="{{ route('tenant.outlets.update', ['tenant' => $tenant->id, 'outlet' => $outlet->id]) }}" class="space-y-5 rounded-xl border border-slate-200 bg-white p-6">
            @csrf @method('put')
            <h2 class="text-lg font-black">Outlet details</h2>
            <div><label class="block text-sm font-bold" for="name">Name</label><input class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2" id="name" name="name" value="{{ old('name', $outlet->name) }}" required></div>
            <div><label class="block text-sm font-bold" for="code">Code</label><input class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 uppercase" id="code" name="code" value="{{ old('code', $outlet->code) }}" required></div>
            <button class="rounded-lg bg-emerald-700 px-4 py-2.5 font-bold text-white">Save changes</button>
        </form>

        <section class="rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-lg font-black">User assignments</h2>
            <form method="post" action="{{ route('tenant.outlets.users.assign', ['tenant' => $tenant->id, 'outlet' => $outlet->id]) }}" class="mt-5 flex gap-3">
                @csrf
                <select class="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2" name="user_id" required>
                    <option value="">Choose tenant user</option>
                    @foreach ($users as $user)<option value="{{ $user->id }}" @disabled(!$user->active)>{{ $user->name }} · {{ $user->email }}{{ $user->active ? '' : ' (disabled)' }}</option>@endforeach
                </select>
                <button class="rounded-lg border border-slate-300 px-4 py-2 font-bold">Assign</button>
            </form>
            <ul class="mt-5 divide-y divide-slate-100">
                @foreach ($users as $user)
                    @if (in_array($user->id, $assignedUserIds, true))
                        <li class="flex items-center gap-3 py-3"><div><p class="font-bold">{{ $user->name }}</p><p class="text-xs text-slate-500">{{ $user->email }}</p></div><form class="ml-auto" method="post" action="{{ route('tenant.outlets.users.remove', ['tenant' => $tenant->id, 'outlet' => $outlet->id, 'user' => $user->id]) }}">@csrf @method('delete')<button class="text-sm font-bold text-rose-700">Remove</button></form></li>
                    @endif
                @endforeach
            </ul>
        </section>
    </div>

    @if ($outlet->status->value === 'active')
        <form method="post" action="{{ route('tenant.outlets.disable', ['tenant' => $tenant->id, 'outlet' => $outlet->id]) }}" class="mt-6 max-w-xl rounded-xl border border-rose-200 bg-rose-50 p-6">
            @csrf
            <h2 class="font-black text-rose-900">Disable outlet</h2>
            <label class="mt-4 block text-sm font-bold" for="reason">Reason</label>
            <textarea class="mt-2 w-full rounded-lg border border-rose-300 px-3 py-2" id="reason" name="reason" minlength="10" maxlength="500" required></textarea>
            <button class="mt-4 rounded-lg bg-rose-700 px-4 py-2.5 font-bold text-white">Disable outlet</button>
        </form>
    @endif
</x-tenant.app-layout>
