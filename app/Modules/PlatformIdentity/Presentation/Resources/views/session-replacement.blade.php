@extends('platform-identity::layout')

@section('title', 'Replace active session')

@section('content')
    <h1>Two active sessions already exist</h1>
    <p class="muted">Choose one existing session to revoke before this sign-in can continue.</p>

    @if ($errors->any())
        <p class="error">{{ $errors->first() }}</p>
    @endif

    <form method="post" action="{{ route('platform.session-replacement.store') }}">
        @csrf
        @foreach ($choices as $token => $choice)
            <label class="session">
                <input name="session_token" type="radio" value="{{ $token }}" required>
                {{ $choice['ip_address'] ?? 'Unknown IP' }} ·
                {{ \Illuminate\Support\Str::limit($choice['user_agent'] ?? 'Unknown device', 90) }}
                <span class="muted">Last active {{ $choice['last_activity_at'] }}</span>
            </label>
        @endforeach

        <button class="danger" type="submit">Revoke selected session and continue</button>
    </form>
@endsection
