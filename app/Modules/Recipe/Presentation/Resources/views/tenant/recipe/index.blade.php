<x-tenant.app-layout
    :tenant="$tenant"
    :context="$context"
    title="Recipes"
    heading="Recipes"
    description="Manage tenant recipe headers and recipe-required policy."
>
    <section class="rounded-lg border border-slate-200 bg-white p-5">
        <h2 class="text-lg font-black">Recipe headers</h2>
        <form class="mt-4 grid gap-3 md:grid-cols-[1fr_160px_160px_auto]" method="post" action="{{ route('tenant.recipes.store', ['tenant' => $tenant->id]) }}">
            @csrf
            <input class="rounded-lg border border-slate-300 px-3 py-2" name="name" placeholder="Recipe name" required maxlength="160">
            <input class="rounded-lg border border-slate-300 px-3 py-2" name="sku" placeholder="SKU" required maxlength="64">
            <label class="flex items-center gap-2 text-sm font-bold">
                <input name="requires_recipe" type="checkbox" value="1" checked>
                Required
            </label>
            <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-bold text-white">Create</button>
        </form>

        <div class="mt-6 space-y-3">
            @forelse ($recipes as $recipe)
                <article class="rounded-lg border border-slate-200 p-4">
                    <form class="grid gap-3 md:grid-cols-[1fr_160px_160px_auto]" method="post" action="{{ route('tenant.recipes.update', ['tenant' => $tenant->id, 'recipe' => $recipe->id]) }}">
                        @csrf
                        @method('put')
                        <input class="rounded-lg border border-slate-300 px-3 py-2" name="name" value="{{ $recipe->name }}" required maxlength="160">
                        <input class="rounded-lg border border-slate-300 px-3 py-2" name="sku" value="{{ $recipe->sku }}" required maxlength="64">
                        <label class="flex items-center gap-2 text-sm font-bold">
                            <input name="requires_recipe" type="checkbox" value="1" @checked($recipe->requires_recipe)>
                            Required
                        </label>
                        <button class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold">Save</button>
                    </form>
                    <form class="mt-3" method="post" action="{{ route('tenant.recipes.status', ['tenant' => $tenant->id, 'recipe' => $recipe->id]) }}">
                        @csrf
                        <input type="hidden" name="status" value="{{ $recipe->status->value === 'active' ? 'inactive' : 'active' }}">
                        <button class="text-sm font-bold text-slate-600">{{ $recipe->status->value === 'active' ? 'Deactivate' : 'Activate' }}</button>
                        <span class="ml-2 text-xs uppercase text-slate-500">{{ $recipe->status->value }}</span>
                    </form>
                </article>
            @empty
                <p class="rounded-lg bg-slate-50 p-4 text-sm text-slate-600">No recipes yet.</p>
            @endforelse
        </div>
    </section>
</x-tenant.app-layout>
