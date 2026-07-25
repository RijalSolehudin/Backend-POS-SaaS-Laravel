@props([
    'title',
    'heading' => null,
    'description' => null,
])

<x-platform.document :title="$title">
    <body
        class="min-h-full bg-slate-50 text-slate-900 antialiased"
        x-data="{ navigationOpen: false }"
        @keydown.escape.window="navigationOpen = false"
    >
        <a href="#main-content" class="platform-skip-link">Skip to content</a>

        <header class="sticky top-0 z-30 border-b border-slate-200/90 bg-white/95 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-screen-2xl items-center gap-5 px-4 sm:px-6 lg:px-8">
                <a href="{{ route('platform.home') }}" class="shrink-0 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2" aria-label="POS Platform home">
                    <x-platform.brand/>
                </a>

                <nav class="ml-6 hidden h-full items-center gap-1 md:flex" aria-label="Platform navigation">
                    <a
                        href="{{ route('platform.tenants.index') }}"
                        @class([
                            'platform-nav-link',
                            'platform-nav-link-active' => request()->routeIs('platform.tenants.*'),
                        ])
                        @if (request()->routeIs('platform.tenants.*')) aria-current="page" @endif
                    >
                        Tenants
                    </a>
                    <a
                        href="{{ route('platform.security') }}"
                        @class([
                            'platform-nav-link',
                            'platform-nav-link-active' => request()->routeIs('platform.security', 'platform.sessions.*', 'platform.recovery-codes.*', 'platform.confirm-sensitive*'),
                        ])
                        @if (request()->routeIs('platform.security', 'platform.sessions.*', 'platform.recovery-codes.*', 'platform.confirm-sensitive*')) aria-current="page" @endif
                    >
                        Security
                    </a>
                </nav>

                <div class="ml-auto hidden items-center gap-4 md:flex">
                    @if (auth('platform')->check())
                        <div class="text-right">
                            <p class="max-w-48 truncate text-sm font-bold text-slate-900">{{ auth('platform')->user()?->name }}</p>
                            <p class="max-w-48 truncate text-xs text-slate-500">{{ auth('platform')->user()?->email }}</p>
                        </div>
                        <form method="post" action="{{ route('platform.logout') }}">
                            @csrf
                            <button class="platform-button platform-button-secondary platform-button-sm" type="submit">
                                Sign out
                            </button>
                        </form>
                    @endif
                </div>

                <button
                    type="button"
                    class="ml-auto grid size-10 place-items-center rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 md:hidden"
                    @click="navigationOpen = ! navigationOpen"
                    :aria-expanded="navigationOpen"
                    aria-controls="platform-mobile-navigation"
                >
                    <span class="sr-only">Toggle navigation</span>
                    <svg x-show="! navigationOpen" class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M2 5.75A.75.75 0 0 1 2.75 5h14.5a.75.75 0 0 1 0 1.5H2.75A.75.75 0 0 1 2 5.75Zm0 4A.75.75 0 0 1 2.75 9h14.5a.75.75 0 0 1 0 1.5H2.75A.75.75 0 0 1 2 9.75Zm.75 3.25a.75.75 0 0 0 0 1.5h14.5a.75.75 0 0 0 0-1.5H2.75Z" clip-rule="evenodd"/>
                    </svg>
                    <svg x-cloak x-show="navigationOpen" class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M4.47 4.47a.75.75 0 0 1 1.06 0L10 8.94l4.47-4.47a.75.75 0 1 1 1.06 1.06L11.06 10l4.47 4.47a.75.75 0 1 1-1.06 1.06L10 11.06l-4.47 4.47a.75.75 0 0 1-1.06-1.06L8.94 10 4.47 5.53a.75.75 0 0 1 0-1.06Z"/>
                    </svg>
                </button>
            </div>

            <div
                id="platform-mobile-navigation"
                x-cloak
                x-show="navigationOpen"
                x-transition.origin.top
                class="border-t border-slate-200 bg-white px-4 py-4 md:hidden"
            >
                <nav class="space-y-1" aria-label="Mobile platform navigation">
                    <a
                        href="{{ route('platform.tenants.index') }}"
                        @class(['platform-nav-link w-full', 'platform-nav-link-active' => request()->routeIs('platform.tenants.*')])
                        @if (request()->routeIs('platform.tenants.*')) aria-current="page" @endif
                    >Tenants</a>
                    <a
                        href="{{ route('platform.security') }}"
                        @class(['platform-nav-link w-full', 'platform-nav-link-active' => request()->routeIs('platform.security', 'platform.sessions.*', 'platform.recovery-codes.*', 'platform.confirm-sensitive*')])
                        @if (request()->routeIs('platform.security', 'platform.sessions.*', 'platform.recovery-codes.*', 'platform.confirm-sensitive*')) aria-current="page" @endif
                    >Security</a>
                </nav>
                @if (auth('platform')->check())
                    <div class="mt-4 border-t border-slate-200 pt-4">
                        <p class="text-sm font-bold text-slate-900">{{ auth('platform')->user()?->name }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">{{ auth('platform')->user()?->email }}</p>
                        <form method="post" action="{{ route('platform.logout') }}" class="mt-3">
                            @csrf
                            <button class="platform-button platform-button-secondary platform-button-sm w-full" type="submit">Sign out</button>
                        </form>
                    </div>
                @endif
            </div>
        </header>

        <main id="main-content" class="mx-auto w-full max-w-screen-2xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
            @if ($heading)
                <div class="mb-8 max-w-3xl">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">Platform administration</p>
                    <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950">{{ $heading }}</h1>
                    @if ($description)
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $description }}</p>
                    @endif
                </div>
            @endif

            <x-platform.flash/>
            <x-platform.form-errors/>

            {{ $slot }}
        </main>
    </body>
</x-platform.document>
