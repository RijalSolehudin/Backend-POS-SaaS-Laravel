@extends('platform-identity::layout')

@section('title', 'Recovery codes')

@section('content')
    <h1>Save your recovery codes</h1>
    <p class="error"><strong>These codes are shown once. Store them in a password manager before continuing.</strong></p>
    <p class="muted">Each code can replace TOTP for one login and is consumed immediately.</p>

    <ul class="codes">
        @foreach ($recoveryCodes as $recoveryCode)
            <li><code>{{ $recoveryCode }}</code></li>
        @endforeach
    </ul>

    <a class="button" href="{{ route('platform.security') }}">I have stored the codes</a>
@endsection
