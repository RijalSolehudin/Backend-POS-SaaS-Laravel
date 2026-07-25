@extends('platform-identity::layout')

@section('title', 'Authentication code')

@section('content')
    <h1>Two-factor authentication</h1>
    <p class="muted">Enter the six-digit code for {{ $email }}, or use one unused recovery code.</p>

    @if ($errors->any())
        <p class="error">{{ $errors->first() }}</p>
    @endif

    <form method="post" action="{{ route('platform.mfa.challenge.store') }}">
        @csrf
        <label for="code">Authentication or recovery code</label>
        <input id="code" name="code" type="text" inputmode="text" autocomplete="one-time-code" maxlength="32" required autofocus>

        <button type="submit">Sign in</button>
    </form>
@endsection
