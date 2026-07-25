@props(['compact' => false])

<span {{ $attributes->class(['inline-flex items-center gap-3']) }}>
    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-emerald-600 text-sm font-black tracking-tight text-white shadow-sm shadow-emerald-950/20">
        PF
    </span>
    @unless ($compact)
        <span>
            <span class="block text-sm font-extrabold tracking-tight text-slate-950">POS Platform</span>
            <span class="block text-xs font-medium text-slate-500">Operations console</span>
        </span>
    @endunless
</span>
