@props(['tenant', 'context' => null, 'title', 'heading', 'description' => null])

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $title }} · {{ $tenant->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
<header class="border-b border-slate-200 bg-white">
    <div class="mx-auto flex max-w-6xl items-center gap-6 px-5 py-4">
        <a href="{{ route('tenant.home', ['tenant' => $tenant->id]) }}">
            <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">Tenant Admin</p>
            <p class="font-black">{{ $tenant->name }}</p>
        </a>
        <nav class="ml-5 flex gap-4 text-sm font-bold">
            <a href="{{ route('tenant.home', ['tenant' => $tenant->id]) }}">Home</a>
            @if (($context ?? null)?->isOwner())
                <a href="{{ route('tenant.outlets.index', ['tenant' => $tenant->id]) }}">Outlets</a>
            @endif
        </nav>
        <form class="ml-auto" method="post" action="{{ route('tenant.logout', ['tenant' => $tenant->id]) }}">
            @csrf
            <button class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold">Sign out</button>
        </form>
    </div>
</header>
<main class="mx-auto max-w-6xl px-5 py-10">
    @if (session('status'))
        <p class="mb-6 rounded-lg bg-emerald-50 p-4 text-emerald-800">{{ session('status') }}</p>
    @endif
    @if ($errors->any())
        <div class="mb-6 rounded-lg bg-rose-50 p-4 text-sm text-rose-800">
            <ul class="list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    <div class="mb-8">
        <h1 class="text-3xl font-black">{{ $heading }}</h1>
        @if ($description)<p class="mt-2 text-slate-600">{{ $description }}</p>@endif
    </div>
    {{ $slot }}
</main>
</body>
</html>
