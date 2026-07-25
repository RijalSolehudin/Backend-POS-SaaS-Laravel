<x-tenant.app-layout
    :tenant="$tenant"
    :context="$context"
    title="Home"
    :heading="'Welcome, '.auth('web')->user()?->name"
    description="Manage the configuration and access boundaries for this business."
>
    <div class="grid gap-5 sm:grid-cols-2">
        @if ($context->isOwner())
            <a href="{{ route('tenant.outlets.index', ['tenant' => $tenant->id]) }}" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="font-black">Outlets</h2>
                <p class="mt-2 text-sm text-slate-600">Create outlets, update their identity, disable operations, and manage user assignments.</p>
            </a>
        @endif
    </div>
</x-tenant.app-layout>
