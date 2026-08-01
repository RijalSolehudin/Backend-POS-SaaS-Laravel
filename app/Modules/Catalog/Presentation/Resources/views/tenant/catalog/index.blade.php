<x-tenant.app-layout
    :tenant="$tenant"
    :context="$context"
    title="Catalog"
    heading="Catalog"
    description="Manage simple categories, products, and outlet availability."
>
    <div class="grid gap-8 lg:grid-cols-[0.9fr_1.1fr]">
        <section class="rounded-lg border border-slate-200 bg-white p-5">
            <h2 class="text-lg font-black">Categories</h2>
            <form class="mt-4 grid gap-3" method="post" action="{{ route('tenant.catalog.categories.store', ['tenant' => $tenant->id]) }}">
                @csrf
                <input class="w-full rounded-lg border border-slate-300 px-3 py-2" name="name" placeholder="Category name" required maxlength="120">
                <select class="w-full rounded-lg border border-slate-300 px-3 py-2" name="parent_id">
                    <option value="">No parent</option>
                    @foreach ($categories->whereNull('parent_id') as $parent)
                        <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                    @endforeach
                </select>
                <input class="w-full rounded-lg border border-slate-300 px-3 py-2" name="display_order" type="number" min="0" step="1" value="0" placeholder="Display order">
                <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-bold text-white">Create</button>
            </form>

            <div class="mt-5 space-y-3">
                @forelse ($categories as $category)
                    <div class="rounded-lg border border-slate-200 p-4">
                        <form class="grid gap-3" method="post" action="{{ route('tenant.catalog.categories.update', ['tenant' => $tenant->id, 'category' => $category->id]) }}">
                            @csrf
                            @method('put')
                            <input class="w-full rounded-lg border border-slate-300 px-3 py-2" name="name" value="{{ $category->name }}" required maxlength="120">
                            <select class="w-full rounded-lg border border-slate-300 px-3 py-2" name="parent_id">
                                <option value="">No parent</option>
                                @foreach ($categories->whereNull('parent_id') as $parent)
                                    @continue($parent->id === $category->id)
                                    <option value="{{ $parent->id }}" @selected($category->parent_id === $parent->id)>{{ $parent->name }}</option>
                                @endforeach
                            </select>
                            <input class="w-full rounded-lg border border-slate-300 px-3 py-2" name="display_order" type="number" min="0" step="1" value="{{ $category->display_order }}">
                            <button class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold">Save</button>
                        </form>
                        <form class="mt-3" method="post" action="{{ route('tenant.catalog.categories.status', ['tenant' => $tenant->id, 'category' => $category->id]) }}">
                            @csrf
                            <input type="hidden" name="status" value="{{ $category->status->value === 'active' ? 'inactive' : 'active' }}">
                            <button class="text-sm font-bold text-slate-600">
                                {{ $category->status->value === 'active' ? 'Deactivate' : 'Activate' }}
                            </button>
                            <span class="ml-2 text-xs uppercase text-slate-500">{{ $category->status->value }}</span>
                        </form>
                    </div>
                @empty
                    <p class="rounded-lg bg-slate-50 p-4 text-sm text-slate-600">No categories yet.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-5">
            <h2 class="text-lg font-black">Products</h2>
            <form class="mt-4 grid gap-3 md:grid-cols-2" method="post" action="{{ route('tenant.catalog.products.store', ['tenant' => $tenant->id]) }}">
                @csrf
                <input class="rounded-lg border border-slate-300 px-3 py-2" name="name" placeholder="Product name" required maxlength="160">
                <input class="rounded-lg border border-slate-300 px-3 py-2" name="sku" placeholder="SKU" required maxlength="64">
                <select class="rounded-lg border border-slate-300 px-3 py-2" name="category_id" required>
                    <option value="">Choose category</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                <div class="grid grid-cols-[1fr_90px] gap-2">
                    <input class="rounded-lg border border-slate-300 px-3 py-2" name="base_price_minor" type="number" min="0" step="1" placeholder="Price minor" required>
                    <input class="rounded-lg border border-slate-300 px-3 py-2 uppercase" name="currency" value="{{ $defaultCurrency }}" maxlength="3" required>
                </div>
                <input class="rounded-lg border border-slate-300 px-3 py-2 md:col-span-2" name="display_order" type="number" min="0" step="1" value="0" placeholder="Display order">
                <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-bold text-white md:col-span-2">Create product</button>
            </form>

            <div class="mt-6 space-y-5">
                @forelse ($products as $product)
                    <article class="rounded-lg border border-slate-200 p-4">
                        <form class="grid gap-3 md:grid-cols-2" method="post" action="{{ route('tenant.catalog.products.update', ['tenant' => $tenant->id, 'product' => $product->id]) }}">
                            @csrf
                            @method('put')
                            <input class="rounded-lg border border-slate-300 px-3 py-2" name="name" value="{{ $product->name }}" required maxlength="160">
                            <input class="rounded-lg border border-slate-300 px-3 py-2" name="sku" value="{{ $product->sku }}" required maxlength="64">
                            <select class="rounded-lg border border-slate-300 px-3 py-2" name="category_id" required>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected($product->category_id === $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <div class="grid grid-cols-[1fr_90px] gap-2">
                                <input class="rounded-lg border border-slate-300 px-3 py-2" name="base_price_minor" type="number" min="0" step="1" value="{{ $product->base_price_minor }}" required>
                                <input class="rounded-lg border border-slate-300 px-3 py-2 uppercase" name="currency" value="{{ $product->currency }}" maxlength="3" required>
                            </div>
                            <input class="rounded-lg border border-slate-300 px-3 py-2 md:col-span-2" name="display_order" type="number" min="0" step="1" value="{{ $product->display_order }}">
                            <button class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold md:col-span-2">Save product</button>
                        </form>

                        <form class="mt-3" method="post" action="{{ route('tenant.catalog.products.status', ['tenant' => $tenant->id, 'product' => $product->id]) }}">
                            @csrf
                            <input type="hidden" name="status" value="{{ $product->status->value === 'active' ? 'inactive' : 'active' }}">
                            <button class="text-sm font-bold text-slate-600">{{ $product->status->value === 'active' ? 'Deactivate product' : 'Activate product' }}</button>
                            <span class="ml-2 text-xs uppercase text-slate-500">{{ $product->status->value }}</span>
                        </form>

                        <div class="mt-4 border-t border-slate-200 pt-4">
                            <p class="text-sm font-black">Variants</p>
                            <form class="mt-3 grid gap-2 rounded-lg bg-slate-50 p-3 md:grid-cols-2" method="post" action="{{ route('tenant.catalog.products.variants.store', ['tenant' => $tenant->id, 'product' => $product->id]) }}">
                                @csrf
                                <input class="rounded-lg border border-slate-300 px-3 py-2 text-sm" name="name" placeholder="Variant name" required maxlength="120">
                                <input class="rounded-lg border border-slate-300 px-3 py-2 text-sm" name="sku" placeholder="Variant SKU" required maxlength="64">
                                <input class="rounded-lg border border-slate-300 px-3 py-2 text-sm" name="price_minor" type="number" min="0" step="1" value="{{ $product->base_price_minor }}" required>
                                <input class="rounded-lg border border-slate-300 px-3 py-2 text-sm uppercase" name="currency" value="{{ $product->currency }}" maxlength="3" required>
                                <label class="flex items-center gap-2 text-sm font-bold">
                                    <input name="is_default" type="checkbox" value="1">
                                    Default
                                </label>
                                <input class="rounded-lg border border-slate-300 px-3 py-2 text-sm" name="display_order" type="number" min="0" step="1" value="0">
                                <button class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-bold md:col-span-2">Create variant</button>
                            </form>

                            <div class="mt-3 grid gap-3">
                                @foreach (($variants->get($product->id) ?? collect()) as $variant)
                                    <form class="grid gap-2 rounded-lg border border-slate-200 p-3 md:grid-cols-2" method="post" action="{{ route('tenant.catalog.products.variants.update', ['tenant' => $tenant->id, 'product' => $product->id, 'variant' => $variant->id]) }}">
                                        @csrf
                                        @method('put')
                                        <input class="rounded-lg border border-slate-300 px-3 py-2 text-sm" name="name" value="{{ $variant->name }}" required maxlength="120">
                                        <input class="rounded-lg border border-slate-300 px-3 py-2 text-sm" name="sku" value="{{ $variant->sku }}" required maxlength="64">
                                        <input class="rounded-lg border border-slate-300 px-3 py-2 text-sm" name="price_minor" type="number" min="0" step="1" value="{{ $variant->price_minor }}" required>
                                        <input class="rounded-lg border border-slate-300 px-3 py-2 text-sm uppercase" name="currency" value="{{ $variant->currency }}" maxlength="3" required>
                                        <label class="flex items-center gap-2 text-sm font-bold">
                                            <input name="is_default" type="checkbox" value="1" @checked($variant->is_default)>
                                            Default
                                        </label>
                                        <input class="rounded-lg border border-slate-300 px-3 py-2 text-sm" name="display_order" type="number" min="0" step="1" value="{{ $variant->display_order }}">
                                        <button class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-bold md:col-span-2">Save variant</button>
                                    </form>
                                    <form method="post" action="{{ route('tenant.catalog.products.variants.status', ['tenant' => $tenant->id, 'product' => $product->id, 'variant' => $variant->id]) }}">
                                        @csrf
                                        <input type="hidden" name="status" value="{{ $variant->status->value === 'active' ? 'inactive' : 'active' }}">
                                        <button class="text-sm font-bold text-slate-600">{{ $variant->status->value === 'active' ? 'Deactivate variant' : 'Activate variant' }}</button>
                                        <span class="ml-2 text-xs uppercase text-slate-500">{{ $variant->status->value }}</span>
                                    </form>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-4 border-t border-slate-200 pt-4">
                            <p class="text-sm font-black">Outlet availability</p>
                            <div class="mt-3 grid gap-3">
                                @foreach ($outlets as $outlet)
                                    @php($availability = $availabilities->get($product->id.'|'.$outlet->outletId))
                                    <form class="grid gap-2 rounded-lg bg-slate-50 p-3 md:grid-cols-[1fr_110px_140px_auto]" method="post" action="{{ route('tenant.catalog.products.availability', ['tenant' => $tenant->id, 'product' => $product->id]) }}">
                                        @csrf
                                        @method('put')
                                        <input type="hidden" name="outlet_id" value="{{ $outlet->outletId }}">
                                        <label class="flex items-center gap-2 text-sm font-bold">
                                            <input name="available" type="checkbox" value="1" @checked(($availability?->available ?? false) === true)>
                                            {{ $outlet->name }}
                                        </label>
                                        <input class="rounded-lg border border-slate-300 px-3 py-2 text-sm" name="price_minor" type="number" min="0" step="1" value="{{ $availability?->price_minor }}" placeholder="Outlet price">
                                        <button class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-bold">Save availability</button>
                                    </form>
                                @endforeach
                            </div>
                        </div>
                    </article>
                @empty
                    <p class="rounded-lg bg-slate-50 p-4 text-sm text-slate-600">No products yet.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-tenant.app-layout>
