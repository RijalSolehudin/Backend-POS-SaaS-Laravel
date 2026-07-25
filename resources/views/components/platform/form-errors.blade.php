@if ($errors->any())
    <div role="alert" aria-live="polite" class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-950">
        <p class="font-bold">Please check the information below.</p>
        <ul class="mt-1 list-inside list-disc space-y-0.5 text-rose-800">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
