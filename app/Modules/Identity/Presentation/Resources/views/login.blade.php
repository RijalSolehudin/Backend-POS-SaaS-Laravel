<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tenant Admin sign in</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
<main class="mx-auto flex min-h-screen max-w-md items-center px-5">
    <section class="w-full rounded-2xl border border-slate-200 bg-white p-8 shadow-xl shadow-slate-900/5">
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">Tenant administration</p>
        <h1 class="mt-2 text-2xl font-black">Sign in to your business</h1>
        <p class="mt-2 text-sm text-slate-600">Your business is resolved securely from your account.</p>
        @if (session('status')) <p class="mt-5 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('status') }}</p> @endif
        <form method="post" action="{{ route('tenant.login.store') }}" class="mt-7 space-y-5">
            @csrf
            <div>
                <label class="block text-sm font-bold" for="email">Email</label>
                <input class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
                @error('email') <p class="mt-2 text-sm text-rose-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-bold" for="password">Password</label>
                <input class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2" id="password" name="password" type="password" autocomplete="current-password" required>
            </div>
            <button class="w-full rounded-lg bg-emerald-700 px-4 py-2.5 font-bold text-white" type="submit">Sign in</button>
        </form>
        <a class="mt-5 block text-center text-sm font-semibold text-emerald-700" href="{{ route('tenant.password.request') }}">Forgot password?</a>
    </section>
</main>
</body>
</html>
