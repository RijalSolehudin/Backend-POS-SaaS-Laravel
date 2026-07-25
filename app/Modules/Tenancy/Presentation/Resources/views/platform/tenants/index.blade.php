<x-platform.app-layout
    title="Tenants"
    heading="Tenants"
    description="Provision and monitor the businesses managed by this platform."
>
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-600">{{ $tenants->total() }} tenant{{ $tenants->total() === 1 ? '' : 's' }} registered</p>
        <a class="platform-button platform-button-primary" href="{{ route('platform.tenants.create') }}">
            <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z"/>
            </svg>
            Provision tenant
        </a>
    </div>

    <section class="platform-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500 sm:px-6" scope="col">Tenant</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500 sm:px-6" scope="col">Configuration</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500 sm:px-6" scope="col">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wide text-slate-500 sm:px-6" scope="col"><span class="sr-only">Open</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($tenants as $tenant)
                        <tr class="transition hover:bg-slate-50/70">
                            <td class="whitespace-nowrap px-5 py-4 sm:px-6">
                                <p class="text-sm font-black text-slate-950">{{ $tenant->name }}</p>
                                <p class="mt-0.5 font-mono text-xs text-slate-500">{{ $tenant->code }}</p>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600 sm:px-6">
                                {{ $tenant->currency }}
                                <span class="mx-1 text-slate-300" aria-hidden="true">·</span>
                                {{ $tenant->timezone }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 sm:px-6">
                                <span @class([
                                    'inline-flex items-center gap-2 rounded-full px-2.5 py-1 text-xs font-bold',
                                    'bg-emerald-50 text-emerald-800' => $tenant->status->value === 'active',
                                    'bg-slate-100 text-slate-700' => $tenant->status->value === 'disabled',
                                ])>
                                    <span @class([
                                        'size-1.5 rounded-full',
                                        'bg-emerald-500' => $tenant->status->value === 'active',
                                        'bg-slate-400' => $tenant->status->value === 'disabled',
                                    ])></span>
                                    {{ ucfirst($tenant->status->value) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right sm:px-6">
                                <a class="text-sm font-bold text-emerald-700 hover:text-emerald-900" href="{{ route('platform.tenants.show', ['tenant' => $tenant->getKey()]) }}">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-16 text-center">
                                <p class="text-sm font-bold text-slate-800">No tenants have been provisioned.</p>
                                <p class="mt-1 text-sm text-slate-500">Create the first controlled tenant to begin onboarding.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($tenants->hasPages())
        <div class="mt-6">{{ $tenants->links() }}</div>
    @endif
</x-platform.app-layout>
