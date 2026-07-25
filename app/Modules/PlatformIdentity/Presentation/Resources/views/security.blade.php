<x-platform.app-layout
    title="Account security"
    heading="Account security"
    description="Review the identity and active sessions protecting your cross-tenant platform access."
>
    <div class="grid gap-5 sm:grid-cols-3">
        <section class="platform-card p-5">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Identity boundary</p>
            <p class="mt-3 text-lg font-black text-slate-950">Platform only</p>
            <p class="mt-1 text-sm leading-5 text-slate-600">Isolated from tenant accounts and roles.</p>
        </section>
        <section class="platform-card p-5">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Second factor</p>
            <p class="mt-3 text-lg font-black text-emerald-700">TOTP enabled</p>
            <p class="mt-1 text-sm leading-5 text-slate-600">Required for every platform sign-in.</p>
        </section>
        <section class="platform-card p-5">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Active sessions</p>
            <p class="mt-3 text-lg font-black text-slate-950">{{ count($sessions) }} of 2</p>
            <p class="mt-1 text-sm leading-5 text-slate-600">15-minute idle and 4-hour absolute limit.</p>
        </section>
    </div>

    <section class="platform-card mt-6">
        <div class="platform-card-header">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-lg font-black tracking-tight text-slate-950">Active platform sessions</h2>
                    <p class="mt-1 text-sm text-slate-600">Revoke any session you do not recognize.</p>
                </div>
                <span class="inline-flex w-fit items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-800">
                    <span class="size-2 rounded-full bg-emerald-500"></span>
                    {{ $user->name }}
                </span>
            </div>
        </div>

        <div class="divide-y divide-slate-200">
            @forelse ($sessions as $session)
                <article class="flex flex-col gap-4 px-5 py-5 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-sm font-black text-slate-950">
                                {{ $session->id === $currentSessionId ? 'Current session' : 'Platform session' }}
                            </h3>
                            @if ($session->id === $currentSessionId)
                                <span class="rounded-full bg-sky-50 px-2 py-0.5 text-[0.6875rem] font-bold uppercase tracking-wide text-sky-700">This device</span>
                            @endif
                        </div>
                        <p class="mt-1 truncate text-sm text-slate-700">
                            {{ $session->ipAddress ?? 'Unknown IP' }}
                            <span class="text-slate-300" aria-hidden="true">·</span>
                            {{ \Illuminate\Support\Str::limit($session->userAgent ?? 'Unknown device', 90) }}
                        </p>
                        <p class="mt-2 text-xs text-slate-500">
                            Started <time datetime="{{ $session->createdAt->format(DATE_ATOM) }}">{{ $session->createdAt->format('M j, Y H:i T') }}</time>
                            <span class="mx-1 text-slate-300" aria-hidden="true">·</span>
                            Last active <time datetime="{{ $session->lastActivityAt->format(DATE_ATOM) }}">{{ $session->lastActivityAt->format('M j, Y H:i T') }}</time>
                        </p>
                    </div>

                    <form method="post" action="{{ route('platform.sessions.revoke', ['session' => $session->id]) }}">
                        @csrf
                        @method('DELETE')
                        <button class="platform-button platform-button-secondary platform-button-sm w-full lg:w-auto" type="submit">
                            {{ $session->id === $currentSessionId ? 'Sign out this session' : 'Revoke session' }}
                        </button>
                    </form>
                </article>
            @empty
                <div class="px-5 py-10 text-center sm:px-6">
                    <p class="text-sm font-bold text-slate-800">Session registration is finishing.</p>
                    <p class="mt-1 text-sm text-slate-500">Refresh this page to see the current session.</p>
                </div>
            @endforelse
        </div>
    </section>

    <section class="mt-6 rounded-2xl border border-rose-200 bg-rose-50/50">
        <div class="border-b border-rose-200 px-5 py-4 sm:px-6">
            <h2 class="text-lg font-black tracking-tight text-rose-950">Recovery credentials</h2>
            <p class="mt-1 text-sm text-rose-800">Regenerating codes permanently invalidates every unused code from the previous set.</p>
        </div>
        <div class="px-5 py-5 sm:px-6">
            <form method="post" action="{{ route('platform.recovery-codes.regenerate') }}">
                @csrf
                <button class="platform-button platform-button-danger" type="submit">Regenerate recovery codes</button>
            </form>
        </div>
    </section>
</x-platform.app-layout>
