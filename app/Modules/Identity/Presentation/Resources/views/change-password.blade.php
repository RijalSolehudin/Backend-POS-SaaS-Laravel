<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Change initial password</title>@vite(['resources/css/app.css'])</head>
<body class="min-h-screen bg-slate-100"><main class="mx-auto flex min-h-screen max-w-md items-center px-5"><section class="w-full rounded-2xl bg-white p-8 shadow-xl">
<p class="text-xs font-bold uppercase tracking-[0.18em] text-amber-700">Security required</p><h1 class="mt-2 text-2xl font-black">Change your initial password</h1><p class="mt-2 text-sm text-slate-600">You must replace the credential supplied during provisioning before continuing.</p>
<form method="post" action="{{ route('tenant.password.update', ['tenant' => $tenant]) }}" class="mt-6 space-y-5">@csrf @method('put')
<div><label class="block text-sm font-bold" for="current_password">Current password</label><input class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2" id="current_password" name="current_password" type="password" required>@error('current_password')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
<div><label class="block text-sm font-bold" for="password">New password</label><input class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2" id="password" name="password" type="password" required>@error('password')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
<div><label class="block text-sm font-bold" for="password_confirmation">Confirm new password</label><input class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2" id="password_confirmation" name="password_confirmation" type="password" required></div>
<button class="w-full rounded-lg bg-emerald-700 px-4 py-2.5 font-bold text-white">Change password and continue</button></form>
</section></main></body></html>
