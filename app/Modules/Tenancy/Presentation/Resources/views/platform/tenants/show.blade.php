<x-platform.app-layout
    :title="$tenant->name"
    :heading="$tenant->name"
    description="Platform-level tenant configuration and lifecycle state."
>
    <div class="mb-6">
        <a class="text-sm font-bold text-emerald-700 hover:text-emerald-900" href="{{ route('platform.tenants.index') }}">← Back to tenants</a>
    </div>

    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <section class="platform-card p-5">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Tenant code</p>
            <p class="mt-3 break-all font-mono text-sm font-bold text-slate-950">{{ $tenant->code }}</p>
        </section>
        <section class="platform-card p-5">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Status</p>
            <p @class([
                'mt-3 text-lg font-black',
                'text-emerald-700' => $tenant->status->value === 'active',
                'text-slate-700' => $tenant->status->value === 'disabled',
            ])>{{ ucfirst($tenant->status->value) }}</p>
        </section>
        <section class="platform-card p-5">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Currency</p>
            <p class="mt-3 text-lg font-black text-slate-950">{{ $tenant->currency }}</p>
        </section>
        <section class="platform-card p-5">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Timezone</p>
            <p class="mt-3 text-sm font-black text-slate-950">{{ $tenant->timezone }}</p>
        </section>
    </div>

    <section class="platform-card mt-6">
        <div class="platform-card-header">
            <h2 class="text-lg font-black tracking-tight text-slate-950">Outlets</h2>
            <p class="mt-1 text-sm text-slate-600">Locations currently provisioned for this tenant.</p>
        </div>
        <div class="divide-y divide-slate-200">
            @foreach ($outlets as $outlet)
                <article class="flex items-center justify-between gap-4 px-5 py-4 sm:px-6">
                    <div>
                        <h3 class="text-sm font-black text-slate-950">{{ $outlet->name }}</h3>
                        <p class="mt-1 font-mono text-xs text-slate-500">{{ $outlet->code }} · {{ $outlet->getKey() }}</p>
                    </div>
                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-800">{{ ucfirst($outlet->status->value) }}</span>
                </article>
            @endforeach
        </div>
    </section>

    @if ($tenant->status->value === 'active')
        <section class="mt-6 rounded-2xl border border-rose-200 bg-rose-50/50">
            <div class="border-b border-rose-200 px-5 py-4 sm:px-6">
                <h2 class="text-lg font-black tracking-tight text-rose-950">Disable tenant</h2>
                <p class="mt-1 text-sm text-rose-800">Effective tenant access will be denied by tenant-context checks on the next request.</p>
            </div>
            <form method="post" action="{{ route('platform.tenants.disable', ['tenant' => $tenant->getKey()]) }}" class="space-y-4 px-5 py-5 sm:px-6">
                @csrf
                <div>
                    <label class="platform-label" for="reason">Reason</label>
                    <textarea class="platform-input min-h-24 max-w-2xl resize-y" id="reason" name="reason" minlength="10" maxlength="500" required></textarea>
                    @error('reason') <p class="mt-2 text-sm text-rose-700">{{ $message }}</p> @enderror
                </div>
                <label class="flex max-w-2xl gap-3 text-sm leading-6 text-rose-950">
                    <input class="mt-1 size-4 accent-rose-600" type="checkbox" required>
                    <span>I understand this disables effective access for this tenant.</span>
                </label>
                <button class="platform-button platform-button-danger" type="submit">Disable tenant</button>
            </form>
        </section>
    @else
        <section class="mt-6 rounded-2xl border border-slate-200 bg-slate-100 p-5 sm:p-6">
            <h2 class="text-sm font-black text-slate-900">Tenant disabled</h2>
            <p class="mt-1 text-sm text-slate-600">
                Disabled {{ $tenant->disabled_at?->format('M j, Y H:i T') ?? 'at an unknown time' }}.
                {{ $tenant->disabled_reason }}
            </p>
        </section>
    @endif
</x-platform.app-layout>
