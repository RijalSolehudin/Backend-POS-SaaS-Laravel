<x-landing-layout>
    <x-slot name="title">Harga</x-slot>

    <!-- Hero Section -->
    <section class="hero-gradient pt-32 pb-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 backdrop-blur rounded-full text-white text-sm font-medium mb-6">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Harga Transparan, Tanpa Biaya Tersembunyi
            </div>
            <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">
                Pilih Paket yang <span class="text-accent">Sesuai Bisnis Anda</span>
            </h1>
            <p class="text-xl text-secondary-200 max-w-2xl mx-auto">
                Mulai trial 14 hari gratis dengan akses penuh ke fitur Professional. Upgrade atau downgrade kapan saja.
            </p>
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="py-16 bg-background -mt-8" x-data="{ billing: 'monthly' }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Billing Toggle -->
            <div class="flex justify-center mb-12">
                <div class="inline-flex items-center gap-3 p-1.5 bg-secondary-100 rounded-xl">
                    <button type="button"
                            @click="billing = 'monthly'"
                            :class="billing === 'monthly' ? 'bg-white shadow-md text-text' : 'text-secondary-600 hover:text-text'"
                            class="px-6 py-2.5 rounded-lg text-sm font-semibold transition-all">
                        Bulanan
                    </button>
                    <button type="button"
                            @click="billing = 'yearly'"
                            :class="billing === 'yearly' ? 'bg-white shadow-md text-text' : 'text-secondary-600 hover:text-text'"
                            class="px-6 py-2.5 rounded-lg text-sm font-semibold transition-all flex items-center gap-2">
                        Tahunan
                        <span class="bg-success text-white text-xs px-2 py-0.5 rounded-full">-20%</span>
                    </button>
                </div>
            </div>

            <!-- Pricing Cards -->
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-16">
                @foreach($plans as $plan)
                    @php
                        $isPopular = $plan->slug === 'growth';
                        $isEnterprise = $plan->slug === 'enterprise';
                    @endphp
                    <div class="relative {{ $isPopular ? 'ring-2 ring-accent shadow-xl scale-105 z-10' : ($isEnterprise ? 'bg-primary text-white' : 'bg-white') }} rounded-2xl p-6 border {{ $isPopular ? 'border-accent' : ($isEnterprise ? 'border-primary' : 'border-border') }} shadow-lg transition-all hover:shadow-xl">
                        @if($isPopular)
                            <div class="absolute -top-4 left-1/2 -translate-x-1/2 px-4 py-1.5 bg-accent text-white text-sm font-semibold rounded-full shadow-lg">
                                Paling Populer
                            </div>
                        @endif

                        <div class="text-center mb-6 {{ $isPopular ? 'pt-2' : '' }}">
                            <h3 class="text-xl font-bold {{ $isEnterprise ? 'text-white' : 'text-text' }} mb-1">{{ $plan->name }}</h3>
                            <p class="{{ $isEnterprise ? 'text-secondary-200' : 'text-text-light' }} text-sm mb-4">{{ $plan->description }}</p>

                            <!-- Monthly Price -->
                            <div x-show="billing === 'monthly'" class="flex items-baseline justify-center gap-1">
                                <span class="text-4xl font-bold {{ $isEnterprise ? 'text-white' : ($isPopular ? 'text-accent' : 'text-text') }}">
                                    Rp {{ number_format($plan->price_monthly / 1000, 0, ',', '.') }}K
                                </span>
                                <span class="{{ $isEnterprise ? 'text-secondary-200' : 'text-text-light' }} text-sm">/bulan</span>
                            </div>

                            <!-- Yearly Price -->
                            <div x-show="billing === 'yearly'" x-cloak class="flex flex-col items-center">
                                <div class="flex items-baseline justify-center gap-1">
                                    <span class="text-4xl font-bold {{ $isEnterprise ? 'text-white' : ($isPopular ? 'text-accent' : 'text-text') }}">
                                        Rp {{ number_format($plan->price_yearly / 1000000, 1, ',', '.') }} Jt
                                    </span>
                                    <span class="{{ $isEnterprise ? 'text-secondary-200' : 'text-text-light' }} text-sm">/tahun</span>
                                </div>
                                <p class="text-success text-sm mt-1 font-medium">
                                    Hemat Rp {{ number_format((($plan->price_monthly * 12) - $plan->price_yearly) / 1000, 0, ',', '.') }}K
                                </p>
                            </div>
                        </div>

                        <!-- Limits -->
                        <div class="space-y-3 mb-6">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 {{ $isEnterprise ? 'text-accent' : 'text-success' }} shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="{{ $isEnterprise ? 'text-secondary-100' : 'text-text-light' }} text-sm">
                                    {{ $plan->max_outlets === -1 ? 'Unlimited' : $plan->max_outlets }} Outlet
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 {{ $isEnterprise ? 'text-accent' : 'text-success' }} shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="{{ $isEnterprise ? 'text-secondary-100' : 'text-text-light' }} text-sm">
                                    {{ $plan->max_users === -1 ? 'Unlimited' : $plan->max_users }} User
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 {{ $isEnterprise ? 'text-accent' : 'text-success' }} shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="{{ $isEnterprise ? 'text-secondary-100' : 'text-text-light' }} text-sm">
                                    {{ $plan->max_products === -1 ? 'Unlimited' : $plan->max_products }} Produk
                                </span>
                            </div>
                        </div>

                        <!-- CTA Button -->
                        <a href="{{ route('tenant.login') }}"
                           class="block w-full py-3 text-center font-semibold rounded-xl transition-colors text-sm {{ $isPopular ? 'bg-accent text-white hover:bg-accent-600 shadow-lg' : ($isEnterprise ? 'bg-accent text-white hover:bg-accent-600' : 'bg-secondary-100 text-text hover:bg-secondary-200') }}">
                            Coba Gratis 14 Hari
                        </a>
                    </div>
                @endforeach
            </div>

            <!-- Feature Comparison Table -->
            <div class="bg-white rounded-2xl border border-border shadow-lg overflow-hidden">
                <div class="px-6 py-4 bg-secondary-50 border-b border-border">
                    <h2 class="text-xl font-bold text-text">Perbandingan Fitur Lengkap</h2>
                    <p class="text-text-light text-sm mt-1">Lihat detail fitur yang tersedia di setiap paket</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-border">
                                <th class="px-6 py-4 text-left text-sm font-semibold text-text w-1/3">Fitur</th>
                                @foreach($plans as $plan)
                                    <th class="px-4 py-4 text-center text-sm font-semibold {{ $plan->slug === 'growth' ? 'text-accent bg-accent/5' : 'text-text' }}">
                                        {{ $plan->name }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <!-- Limits Section -->
                            <tr class="bg-secondary-50">
                                <td colspan="5" class="px-6 py-3 text-sm font-semibold text-text">Batasan</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-3 text-sm text-text-light">Jumlah Outlet</td>
                                @foreach($plans as $plan)
                                    <td class="px-4 py-3 text-center text-sm {{ $plan->slug === 'growth' ? 'bg-accent/5' : '' }}">
                                        {{ $plan->max_outlets === -1 ? 'Unlimited' : $plan->max_outlets }}
                                    </td>
                                @endforeach
                            </tr>
                            <tr>
                                <td class="px-6 py-3 text-sm text-text-light">Jumlah User</td>
                                @foreach($plans as $plan)
                                    <td class="px-4 py-3 text-center text-sm {{ $plan->slug === 'growth' ? 'bg-accent/5' : '' }}">
                                        {{ $plan->max_users === -1 ? 'Unlimited' : $plan->max_users }}
                                    </td>
                                @endforeach
                            </tr>
                            <tr>
                                <td class="px-6 py-3 text-sm text-text-light">Jumlah Produk</td>
                                @foreach($plans as $plan)
                                    <td class="px-4 py-3 text-center text-sm {{ $plan->slug === 'growth' ? 'bg-accent/5' : '' }}">
                                        {{ $plan->max_products === -1 ? 'Unlimited' : $plan->max_products }}
                                    </td>
                                @endforeach
                            </tr>

                            <!-- POS Section -->
                            <tr class="bg-secondary-50">
                                <td colspan="5" class="px-6 py-3 text-sm font-semibold text-text">POS & Transaksi</td>
                            </tr>
                            @php
                                $posFeatures = [
                                    ['name' => 'POS Core (Order, Payment, Receipt)', 'key' => 'pos_core'],
                                    ['name' => 'Shift & Session Management', 'key' => 'pos_core'],
                                    ['name' => 'Held Order', 'key' => 'pos_core'],
                                    ['name' => 'Multi-Payment', 'key' => 'multi_payment'],
                                    ['name' => 'Split Bill', 'key' => 'pos_core'],
                                    ['name' => 'Riwayat Transaksi', 'key' => 'pos_core'],
                                ];
                            @endphp
                            @foreach($posFeatures as $feature)
                                <tr>
                                    <td class="px-6 py-3 text-sm text-text-light">{{ $feature['name'] }}</td>
                                    @foreach($plans as $plan)
                                        <td class="px-4 py-3 text-center {{ $plan->slug === 'growth' ? 'bg-accent/5' : '' }}">
                                            @if($plan->features[$feature['key']] ?? false)
                                                <svg class="w-5 h-5 text-success mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                </svg>
                                            @else
                                                <svg class="w-5 h-5 text-secondary-300 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                </svg>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach

                            <!-- Menu & Products Section -->
                            <tr class="bg-secondary-50">
                                <td colspan="5" class="px-6 py-3 text-sm font-semibold text-text">Menu & Produk</td>
                            </tr>
                            @php
                                $menuFeatures = [
                                    ['name' => 'Produk & Kategori', 'key' => 'product_management'],
                                    ['name' => 'Variant (Size, Topping)', 'key' => 'product_variant'],
                                    ['name' => 'Modifier Groups', 'key' => 'product_variant'],
                                    ['name' => 'Combo / Paket', 'key' => 'product_combo'],
                                    ['name' => 'Diskon & Promo', 'key' => 'discount_promo'],
                                    ['name' => 'Loyalty Points', 'key' => 'loyalty_points'],
                                ];
                            @endphp
                            @foreach($menuFeatures as $feature)
                                <tr>
                                    <td class="px-6 py-3 text-sm text-text-light">{{ $feature['name'] }}</td>
                                    @foreach($plans as $plan)
                                        <td class="px-4 py-3 text-center {{ $plan->slug === 'growth' ? 'bg-accent/5' : '' }}">
                                            @if($plan->features[$feature['key']] ?? false)
                                                <svg class="w-5 h-5 text-success mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                </svg>
                                            @else
                                                <svg class="w-5 h-5 text-secondary-300 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                </svg>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach

                            <!-- Inventory Section -->
                            <tr class="bg-secondary-50">
                                <td colspan="5" class="px-6 py-3 text-sm font-semibold text-text">Inventory & Stock</td>
                            </tr>
                            @php
                                $inventoryFeatures = [
                                    ['name' => 'Stock Tracking', 'key' => 'inventory_basic'],
                                    ['name' => 'Low Stock Alert', 'key' => 'inventory_basic'],
                                    ['name' => 'Stock Adjustment', 'key' => 'inventory_basic'],
                                    ['name' => 'Batch & Expiry Tracking', 'key' => 'inventory_advanced'],
                                    ['name' => 'Purchase Order (PO)', 'key' => 'inventory_advanced'],
                                    ['name' => 'Goods Receive (GR)', 'key' => 'inventory_advanced'],
                                    ['name' => 'Stock Transfer Antar Outlet', 'key' => 'stock_transfer'],
                                    ['name' => 'Recipe / BOM', 'key' => 'recipe_bom'],
                                    ['name' => 'Waste Logging', 'key' => 'inventory_advanced'],
                                    ['name' => 'Supplier Management', 'key' => 'inventory_advanced'],
                                ];
                            @endphp
                            @foreach($inventoryFeatures as $feature)
                                <tr>
                                    <td class="px-6 py-3 text-sm text-text-light">{{ $feature['name'] }}</td>
                                    @foreach($plans as $plan)
                                        <td class="px-4 py-3 text-center {{ $plan->slug === 'growth' ? 'bg-accent/5' : '' }}">
                                            @if($plan->features[$feature['key']] ?? false)
                                                <svg class="w-5 h-5 text-success mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                </svg>
                                            @else
                                                <svg class="w-5 h-5 text-secondary-300 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                </svg>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach

                            <!-- Operations Section -->
                            <tr class="bg-secondary-50">
                                <td colspan="5" class="px-6 py-3 text-sm font-semibold text-text">Operasional</td>
                            </tr>
                            @php
                                $opsFeatures = [
                                    ['name' => 'Table Management', 'key' => 'table_management'],
                                    ['name' => 'QR Self-Order', 'key' => 'qr_order'],
                                    ['name' => 'Waiter App', 'key' => 'waiter_app'],
                                    ['name' => 'Kitchen Display System (KDS)', 'key' => 'kds'],
                                    ['name' => 'Manager Authorization', 'key' => 'manager_authorization'],
                                ];
                            @endphp
                            @foreach($opsFeatures as $feature)
                                <tr>
                                    <td class="px-6 py-3 text-sm text-text-light">{{ $feature['name'] }}</td>
                                    @foreach($plans as $plan)
                                        <td class="px-4 py-3 text-center {{ $plan->slug === 'growth' ? 'bg-accent/5' : '' }}">
                                            @if($plan->features[$feature['key']] ?? false)
                                                <svg class="w-5 h-5 text-success mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                </svg>
                                            @else
                                                <svg class="w-5 h-5 text-secondary-300 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                </svg>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach

                            <!-- Reports & Integration Section -->
                            <tr class="bg-secondary-50">
                                <td colspan="5" class="px-6 py-3 text-sm font-semibold text-text">Laporan & Integrasi</td>
                            </tr>
                            @php
                                $reportFeatures = [
                                    ['name' => 'Laporan Penjualan Dasar', 'key' => 'basic_reports'],
                                    ['name' => 'Export Excel / PDF', 'key' => 'export_excel_pdf'],
                                    ['name' => 'API Access', 'key' => 'api_access'],
                                    ['name' => 'Custom Branding', 'key' => 'custom_branding'],
                                ];
                            @endphp
                            @foreach($reportFeatures as $feature)
                                <tr>
                                    <td class="px-6 py-3 text-sm text-text-light">{{ $feature['name'] }}</td>
                                    @foreach($plans as $plan)
                                        <td class="px-4 py-3 text-center {{ $plan->slug === 'growth' ? 'bg-accent/5' : '' }}">
                                            @if($plan->features[$feature['key']] ?? false)
                                                <svg class="w-5 h-5 text-success mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                </svg>
                                            @else
                                                <svg class="w-5 h-5 text-secondary-300 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                </svg>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach

                            <!-- Support Section -->
                            <tr class="bg-secondary-50">
                                <td colspan="5" class="px-6 py-3 text-sm font-semibold text-text">Dukungan</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-3 text-sm text-text-light">Email Support</td>
                                @foreach($plans as $plan)
                                    <td class="px-4 py-3 text-center {{ $plan->slug === 'growth' ? 'bg-accent/5' : '' }}">
                                        <svg class="w-5 h-5 text-success mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                    </td>
                                @endforeach
                            </tr>
                            <tr>
                                <td class="px-6 py-3 text-sm text-text-light">Onboarding Session</td>
                                @foreach($plans as $plan)
                                    <td class="px-4 py-3 text-center {{ $plan->slug === 'growth' ? 'bg-accent/5' : '' }}">
                                        @if($plan->slug === 'enterprise')
                                            <svg class="w-5 h-5 text-success mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                        @else
                                            <svg class="w-5 h-5 text-secondary-300 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                            </svg>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                            <tr>
                                <td class="px-6 py-3 text-sm text-text-light">Dedicated Account Manager</td>
                                @foreach($plans as $plan)
                                    <td class="px-4 py-3 text-center {{ $plan->slug === 'growth' ? 'bg-accent/5' : '' }}">
                                        @if($plan->slug === 'enterprise')
                                            <svg class="w-5 h-5 text-success mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                        @else
                                            <svg class="w-5 h-5 text-secondary-300 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                            </svg>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                            <tr>
                                <td class="px-6 py-3 text-sm text-text-light">SLA 99.9% Uptime</td>
                                @foreach($plans as $plan)
                                    <td class="px-4 py-3 text-center {{ $plan->slug === 'growth' ? 'bg-accent/5' : '' }}">
                                        @if($plan->slug === 'enterprise')
                                            <svg class="w-5 h-5 text-success mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                        @else
                                            <svg class="w-5 h-5 text-secondary-300 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                            </svg>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-16 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-text mb-4">Pertanyaan Umum</h2>
                <p class="text-text-light">Jawaban untuk pertanyaan yang sering diajukan</p>
            </div>

            <div class="space-y-4" x-data="{ open: null }">
                <!-- FAQ 1 -->
                <div class="bg-background rounded-xl border border-border overflow-hidden">
                    <button @click="open = open === 1 ? null : 1" class="w-full px-6 py-4 text-left flex items-center justify-between">
                        <span class="font-semibold text-text">Bagaimana cara kerja trial 14 hari?</span>
                        <svg class="w-5 h-5 text-text-light transition-transform" :class="open === 1 ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open === 1" class="px-6 pb-4">
                        <p class="text-text-light">
                            Setelah registrasi, Anda langsung mendapatkan akses penuh ke semua fitur Professional selama 14 hari tanpa perlu memasukkan kartu kredit. Setelah trial berakhir, pilih paket yang sesuai kebutuhan Anda.
                        </p>
                    </div>
                </div>

                <!-- FAQ 2 -->
                <div class="bg-background rounded-xl border border-border overflow-hidden">
                    <button @click="open = open === 2 ? null : 2" class="w-full px-6 py-4 text-left flex items-center justify-between">
                        <span class="font-semibold text-text">Apakah bisa upgrade atau downgrade paket?</span>
                        <svg class="w-5 h-5 text-text-light transition-transform" :class="open === 2 ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open === 2" class="px-6 pb-4">
                        <p class="text-text-light">
                            Ya! Anda bisa upgrade kapan saja dan hanya membayar selisih pro-rata. Untuk downgrade, langganan saat ini akan tetap aktif hingga akhir periode, kemudian berpindah ke paket yang lebih rendah.
                        </p>
                    </div>
                </div>

                <!-- FAQ 3 -->
                <div class="bg-background rounded-xl border border-border overflow-hidden">
                    <button @click="open = open === 3 ? null : 3" class="w-full px-6 py-4 text-left flex items-center justify-between">
                        <span class="font-semibold text-text">Metode pembayaran apa yang diterima?</span>
                        <svg class="w-5 h-5 text-text-light transition-transform" :class="open === 3 ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open === 3" class="px-6 pb-4">
                        <p class="text-text-light">
                            Kami menerima pembayaran via Virtual Account (BCA, Mandiri, BNI, BRI), QRIS, kartu kredit/debit, dan e-wallet (OVO, GoPay, DANA). Powered by Xendit.
                        </p>
                    </div>
                </div>

                <!-- FAQ 4 -->
                <div class="bg-background rounded-xl border border-border overflow-hidden">
                    <button @click="open = open === 4 ? null : 4" class="w-full px-6 py-4 text-left flex items-center justify-between">
                        <span class="font-semibold text-text">Apa yang terjadi jika tidak bayar setelah trial?</span>
                        <svg class="w-5 h-5 text-text-light transition-transform" :class="open === 4 ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open === 4" class="px-6 pb-4">
                        <p class="text-text-light">
                            Akun akan masuk mode "frozen" - Anda tetap bisa login dan melihat data, tapi tidak bisa melakukan transaksi baru. Data Anda aman dan akan dipertahankan selama 1 tahun. Kapan saja bisa reaktivasi dengan memilih paket.
                        </p>
                    </div>
                </div>

                <!-- FAQ 5 -->
                <div class="bg-background rounded-xl border border-border overflow-hidden">
                    <button @click="open = open === 5 ? null : 5" class="w-full px-6 py-4 text-left flex items-center justify-between">
                        <span class="font-semibold text-text">Apakah ada kontrak jangka panjang?</span>
                        <svg class="w-5 h-5 text-text-light transition-transform" :class="open === 5 ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open === 5" class="px-6 pb-4">
                        <p class="text-text-light">
                            Tidak ada kontrak! Semua paket bisa dibatalkan kapan saja. Kami tidak melakukan refund karena sudah ada trial 14 hari gratis untuk mencoba semua fitur.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 bg-background">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="hero-gradient rounded-3xl p-12 text-center relative overflow-hidden">
                <div class="absolute top-0 right-0 w-64 h-64 bg-accent/20 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 left-0 w-48 h-48 bg-primary-400/20 rounded-full blur-3xl"></div>

                <div class="relative z-10">
                    <h2 class="text-3xl font-bold text-white mb-4">
                        Siap Memulai?
                    </h2>
                    <p class="text-xl text-secondary-200 mb-8 max-w-2xl mx-auto">
                        Daftar sekarang dan dapatkan trial 14 hari gratis. Setup cepat, tanpa kartu kredit.
                    </p>
                    <a href="{{ route('tenant.login') }}" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-accent hover:bg-accent-600 text-white font-bold rounded-xl transition-all shadow-xl hover:shadow-2xl">
                        <span>Mulai Trial Gratis</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </section>
</x-landing-layout>
