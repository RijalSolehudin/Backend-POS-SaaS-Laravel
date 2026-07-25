<x-platform.document :title="$heading">
    <body class="min-h-full bg-slate-950 text-white antialiased">
        <main class="relative grid min-h-screen place-items-center overflow-hidden px-5 py-12">
            <div class="absolute inset-0 platform-grid opacity-25" aria-hidden="true"></div>
            <div class="absolute left-1/2 top-1/3 size-96 -translate-x-1/2 rounded-full bg-emerald-500/15 blur-3xl" aria-hidden="true"></div>

            <section class="relative w-full max-w-lg rounded-2xl border border-white/10 bg-white/[0.06] p-7 shadow-2xl shadow-black/20 backdrop-blur sm:p-10">
                <x-platform.brand class="[&>span:first-child]:ring-1 [&>span:first-child]:ring-white/20 [&>span>span:first-child]:text-white [&>span>span:last-child]:text-slate-400"/>

                <p class="mt-10 text-xs font-bold uppercase tracking-[0.2em] text-emerald-400">Error {{ $status }}</p>
                <h1 class="mt-3 text-3xl font-black tracking-tight text-white">{{ $heading }}</h1>
                <p class="mt-3 text-sm leading-6 text-slate-300">{{ $description }}</p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('platform.home') }}" class="platform-button platform-button-primary">Return to platform</a>
                    <button type="button" class="platform-button border-white/20 bg-white/10 text-white hover:bg-white/15" onclick="history.back()">
                        Go back
                    </button>
                </div>
            </section>
        </main>
    </body>
</x-platform.document>
