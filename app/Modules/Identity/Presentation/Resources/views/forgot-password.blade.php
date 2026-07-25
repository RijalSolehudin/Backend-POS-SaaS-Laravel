<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Reset password</title>@vite(['resources/css/app.css'])</head>
<body class="min-h-screen bg-slate-100"><main class="mx-auto flex min-h-screen max-w-md items-center px-5"><section class="w-full rounded-2xl bg-white p-8 shadow-xl">
<h1 class="text-2xl font-black">Reset your password</h1><p class="mt-2 text-sm text-slate-600">Enter the email for your tenant account.</p>
@if (session('status')) <p class="mt-5 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('status') }}</p> @endif
<form method="post" action="{{ route('tenant.password.email') }}" class="mt-6 space-y-5">@csrf
<div><label class="block text-sm font-bold" for="email">Email</label><input class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2" id="email" name="email" type="email" value="{{ old('email') }}" required>@error('email')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
<button class="w-full rounded-lg bg-emerald-700 px-4 py-2.5 font-bold text-white">Send reset link</button></form>
<a class="mt-5 block text-center text-sm font-semibold text-emerald-700" href="{{ route('tenant.login') }}">Back to sign in</a>
</section></main></body></html>
