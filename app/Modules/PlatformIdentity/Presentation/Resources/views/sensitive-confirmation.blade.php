@extends('platform-identity::layout')

@section('title', 'Confirm sensitive action')

@section('content')
    <h1>Confirm sensitive action</h1>
    <p class="muted">Enter your password and a current TOTP or unused recovery code. Confirmation is valid for ten minutes in this session only.</p>

    @if ($errors->any())
        <p class="error">{{ $errors->first() }}</p>
    @endif

    <form method="post" action="{{ route('platform.confirm-sensitive.store') }}">
        @csrf
        <label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="current-password" maxlength="128" required>

        <label for="code">Authentication or recovery code</label>
        <input id="code" name="code" type="text" autocomplete="one-time-code" maxlength="32" required>

        <button type="submit">Confirm</button>
    </form>
@endsection
