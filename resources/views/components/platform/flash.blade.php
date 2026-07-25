@if (session('status'))
    <div role="status" class="mb-6 flex gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
        <svg class="mt-0.5 size-5 shrink-0 text-emerald-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.236 4.45-1.95-1.95a.75.75 0 0 0-1.06 1.061l2.57 2.57a.75.75 0 0 0 1.137-.089l3.753-5.16Z" clip-rule="evenodd"/>
        </svg>
        <p>{{ session('status') }}</p>
    </div>
@endif
