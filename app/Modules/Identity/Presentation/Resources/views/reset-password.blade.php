<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Choose new password</title>@vite(['resources/css/app.css'])</head>
<body class="min-h-screen bg-slate-100"><main class="mx-auto flex min-h-screen max-w-md items-center px-5"><section class="w-full rounded-2xl bg-white p-8 shadow-xl">
<h1 class="text-2xl font-black">Choose a new password</h1>
<form method="post" action="{{ route('tenant.password.reset.store') }}" class="mt-6 space-y-5">@csrf<input type="hidden" name="token" value="{{ $token }}">
<div><label class="block text-sm font-bold" for="email">Email</label><input class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2" id="email" name="email" type="email" value="{{ old('email', $email) }}" required>@error('email')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
<div><label class="block text-sm font-bold" for="password">New password</label><input class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2" id="password" name="password" type="password" required></div>
<div><label class="block text-sm font-bold" for="password_confirmation">Confirm password</label><input class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2" id="password_confirmation" name="password_confirmation" type="password" required></div>
<button class="w-full rounded-lg bg-emerald-700 px-4 py-2.5 font-bold text-white">Reset password</button></form>
</section></main></body></html>
