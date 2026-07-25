@extends('platform-identity::layout')

@section('title', 'Set up TOTP')

@section('content')
    <h1>Set up two-factor authentication</h1>
    <p class="muted">Scan this QR code with an RFC 6238 compatible authenticator app, then enter its six-digit code.</p>

    <div class="qr" aria-label="TOTP enrollment QR code">{!! $qrSvg !!}</div>

    <p>Manual secret for <strong>{{ $email }}</strong>:</p>
    <p><code>{{ $secret }}</code></p>

    @if ($errors->any())
        <p class="error">{{ $errors->first() }}</p>
    @endif

    <form method="post" action="{{ route('platform.mfa.enroll.store') }}">
        @csrf
        <label for="code">Six-digit authentication code</label>
        <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" autocomplete="one-time-code" maxlength="6" required autofocus>

        <button type="submit">Confirm and activate</button>
    </form>
@endsection
