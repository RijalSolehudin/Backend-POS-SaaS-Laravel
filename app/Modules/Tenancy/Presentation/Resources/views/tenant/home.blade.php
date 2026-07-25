<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $tenant->name }} Admin</title>@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
<header class="border-b border-slate-200 bg-white"><div class="mx-auto flex max-w-6xl items-center px-5 py-4"><div><p class="text-xs font-bold uppercase tracking-wider text-emerald-700">Tenant Admin</p><p class="font-black">{{ $tenant->name }}</p></div><form class="ml-auto" method="post" action="{{ route('tenant.logout', ['tenant' => $tenant->id]) }}">@csrf<button class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold">Sign out</button></form></div></header>
<main class="mx-auto max-w-6xl px-5 py-10">@if(session('status'))<p class="mb-6 rounded-lg bg-emerald-50 p-4 text-emerald-800">{{ session('status') }}</p>@endif<h1 class="text-3xl font-black">Welcome, {{ auth('web')->user()?->name }}</h1><p class="mt-3 text-slate-600">Your tenant identity session is active. Business administration capabilities will be added by the following work packages.</p></main>
</body></html>
