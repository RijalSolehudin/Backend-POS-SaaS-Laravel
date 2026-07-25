<x-platform.guest-layout
    title="Replace active session"
    heading="Two active sessions already exist"
    description="Choose one existing session to revoke before this sign-in can continue."
>
    <form method="post" action="{{ route('platform.session-replacement.store') }}" class="space-y-4">
        @csrf

        @foreach ($choices as $token => $choice)
            <label class="flex cursor-pointer gap-3 rounded-xl border border-slate-200 p-4 transition hover:border-emerald-400 hover:bg-emerald-50/40">
                <input class="mt-1 size-4 accent-emerald-600" name="session_token" type="radio" value="{{ $token }}" required>
                <span class="min-w-0">
                    <span class="block text-sm font-bold text-slate-900">{{ $choice['ip_address'] ?? 'Unknown IP' }}</span>
                    <span class="mt-1 block truncate text-xs text-slate-600">{{ \Illuminate\Support\Str::limit($choice['user_agent'] ?? 'Unknown device', 90) }}</span>
                    <span class="mt-2 block text-xs font-medium text-slate-500">Last active {{ $choice['last_activity_at'] }}</span>
                </span>
            </label>
        @endforeach

        <button class="platform-button platform-button-danger w-full" type="submit">Revoke selected session and continue</button>
    </form>
</x-platform.guest-layout>
