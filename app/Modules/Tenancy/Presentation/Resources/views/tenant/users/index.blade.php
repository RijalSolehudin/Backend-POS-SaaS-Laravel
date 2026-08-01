<x-tenant.app-layout :tenant="$tenant" :context="$context" title="Users and roles" heading="Users and roles" description="Assign predefined tenant roles without changing the system permission matrix.">
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr><th class="px-5 py-3">User</th><th class="px-5 py-3">Roles</th><th class="px-5 py-3">Assign role</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($users as $user)
                    <tr>
                        <td class="px-5 py-4">
                            <p class="font-bold">{{ $user->name }}</p>
                            <p class="text-xs text-slate-500">{{ $user->email }}{{ $user->active ? '' : ' · disabled' }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex flex-wrap gap-2">
                                @forelse ($user->roles as $role)
                                    <form method="post" action="{{ route('tenant.users.roles.destroy', ['tenant' => $tenant->id, 'user' => $user->id, 'role' => $role]) }}">
                                        @csrf @method('delete')
                                        <button class="rounded-full border border-slate-300 px-3 py-1 text-xs font-bold text-slate-700">{{ str_replace('_', ' ', $role) }} ×</button>
                                    </form>
                                @empty
                                    <span class="text-slate-500">No role assigned</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            <form method="post" action="{{ route('tenant.users.roles.store', ['tenant' => $tenant->id, 'user' => $user->id]) }}" class="flex gap-2">
                                @csrf
                                <select class="min-w-0 rounded-lg border border-slate-300 px-3 py-2" name="role" required>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role['value'] }}">{{ $role['label'] }}</option>
                                    @endforeach
                                </select>
                                <button class="rounded-lg border border-slate-300 px-4 py-2 font-bold">Assign</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-tenant.app-layout>
