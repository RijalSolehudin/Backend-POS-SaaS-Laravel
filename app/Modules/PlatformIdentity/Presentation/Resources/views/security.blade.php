@extends('platform-identity::layout')

@section('title', 'Account security')

@section('content')
    <h1>Account security</h1>
    <p><strong>{{ $user->name }}</strong><br>{{ $user->email }}</p>
    <p class="muted">Platform identity is isolated from tenant accounts. TOTP is active and remember-me is unavailable.</p>

    <h2>Active sessions</h2>
    @forelse ($sessions as $session)
        <div class="session">
            <p><strong>{{ $session->id === $currentSessionId ? 'Current session' : 'Platform session' }}</strong></p>
            <p>{{ $session->ipAddress ?? 'Unknown IP' }} · {{ \Illuminate\Support\Str::limit($session->userAgent ?? 'Unknown device', 90) }}</p>
            <p class="muted">Started {{ $session->createdAt->format('Y-m-d H:i T') }} · Last active {{ $session->lastActivityAt->format('Y-m-d H:i T') }}</p>
            <form method="post" action="{{ route('platform.sessions.revoke', ['session' => $session->id]) }}">
                @csrf
                @method('DELETE')
                <button class="danger" type="submit">Revoke session</button>
            </form>
        </div>
    @empty
        <p class="muted">The current session will appear after this response is persisted.</p>
    @endforelse

    <h2>Recovery codes</h2>
    <p class="muted">Regenerating codes permanently invalidates every unused code from the previous set.</p>
    <form method="post" action="{{ route('platform.recovery-codes.regenerate') }}">
        @csrf
        <button class="danger" type="submit">Regenerate recovery codes</button>
    </form>

    <form method="post" action="{{ route('platform.logout') }}">
        @csrf
        <button type="submit">Sign out</button>
    </form>
@endsection
