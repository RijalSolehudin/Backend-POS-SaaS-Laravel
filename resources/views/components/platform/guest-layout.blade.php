@props([
    'title',
    'heading',
    'description' => null,
])

<x-platform.document :title="$title">
    <body class="min-h-full bg-slate-950 text-slate-900 antialiased">
        <main class="grid min-h-screen lg:grid-cols-[minmax(22rem,0.9fr)_minmax(34rem,1.1fr)]">
            <section class="relative hidden overflow-hidden border-r border-white/10 bg-slate-950 p-12 text-white lg:flex lg:flex-col lg:justify-between">
                <div class="absolute inset-0 platform-grid opacity-30" aria-hidden="true"></div>
                <div class="absolute -left-24 top-20 size-80 rounded-full bg-emerald-500/20 blur-3xl" aria-hidden="true"></div>
                <div class="absolute bottom-8 right-0 size-64 rounded-full bg-sky-500/10 blur-3xl" aria-hidden="true"></div>

                <x-platform.brand class="relative [&>span:first-child]:ring-1 [&>span:first-child]:ring-white/20 [&>span>span:first-child]:text-white [&>span>span:last-child]:text-slate-400"/>

                <div class="relative max-w-lg">
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-emerald-400">Protected workspace</p>
                    <p class="mt-5 text-4xl font-black leading-tight tracking-[-0.035em] text-white">
                        Platform operations, isolated by design.
                    </p>
                    <p class="mt-5 max-w-md text-base leading-7 text-slate-400">
                        Dedicated credentials, mandatory multi-factor authentication, and an independent session boundary protect cross-tenant operations.
                    </p>
                </div>

                <p class="relative text-xs leading-5 text-slate-500">
                    Platform access is separate from every tenant account.
                </p>
            </section>

            <section class="flex min-h-screen items-center justify-center bg-slate-50 px-5 py-10 sm:px-8">
                <div class="w-full max-w-md">
                    <x-platform.brand class="mb-10 lg:hidden"/>

                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-950/5 sm:p-8">
                        <div class="mb-7">
                            <p class="mb-2 text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">Platform administration</p>
                            <h1 class="text-2xl font-black tracking-tight text-slate-950">{{ $heading }}</h1>
                            @if ($description)
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $description }}</p>
                            @endif
                        </div>

                        <x-platform.flash/>
                        <x-platform.form-errors/>

                        {{ $slot }}
                    </div>

                    <p class="mt-6 text-center text-xs leading-5 text-slate-500">
                        This area is monitored. Authentication and security events are audited.
                    </p>
                </div>
            </section>
        </main>
    </body>
</x-platform.document>
