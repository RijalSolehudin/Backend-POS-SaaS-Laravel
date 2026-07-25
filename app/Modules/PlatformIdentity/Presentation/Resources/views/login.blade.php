@extends('platform-identity::layout')

@section('title', 'Sign in')

@section('content')
    <h1>Platform Administrator</h1>
    <p class="muted">Sign in with your dedicated platform account. Tenant credentials are not accepted here.</p>

    @if ($errors->any())
        <p class="error">{{ $errors->first() }}</p>
    @endif

    <form method="post" action="{{ route('platform.login.store') }}">
        @csrf
        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>

        <label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="current-password" maxlength="128" required>

        <button type="submit">Continue</button>
    </form>
@endsection
