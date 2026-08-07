<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Ultimate POS - Sistem Point of Sale premium untuk restoran, kafe, dan bisnis F&B. Multi-outlet, inventory tracking, KDS, dan mobile app.">

    <title>{{ $title ?? config('app.name', 'Ultimate POS') }} - Solusi POS Premium</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Premium Design System - Option C Styles */
        .gradient-text {
            background: linear-gradient(135deg, #7C3AED 0%, #F59E0B 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-gradient {
            background: linear-gradient(135deg, #1F2937 0%, #374151 40%, #7C3AED 100%);
        }

        .feature-card:hover {
            transform: translateY(-4px);
        }

        .animate-float {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Premium glow effects */
        .glow-purple {
            box-shadow: 0 0 60px rgba(124, 58, 237, 0.3);
        }

        .glow-amber {
            box-shadow: 0 0 40px rgba(245, 158, 11, 0.3);
        }

        /* Animated gradient border */
        .gradient-border {
            position: relative;
            background: linear-gradient(135deg, #7C3AED, #F59E0B);
            padding: 2px;
            border-radius: 1rem;
        }

        .gradient-border-inner {
            background: white;
            border-radius: calc(1rem - 2px);
        }

        /* Smooth scroll */
        html {
            scroll-behavior: smooth;
        }
    </style>

    @stack('styles')
</head>
<body class="bg-background text-text font-sans antialiased" x-data="{ mobileMenuOpen: false }">
    <!-- Navigation - Premium Style -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-lg border-b border-primary-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <a href="/" class="flex items-center gap-2.5">
                    <div class="w-9 h-9 bg-gradient-to-br from-primary to-primary-500 rounded-xl flex items-center justify-center shadow-lg shadow-primary/20">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <span class="text-lg font-bold text-secondary-900">Ultimate POS</span>
                </a>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center gap-8">
                    <a href="/#features" class="text-secondary-600 hover:text-primary transition-colors font-medium">Fitur</a>
                    <a href="/#modules" class="text-secondary-600 hover:text-primary transition-colors font-medium">Modul</a>
                    <a href="{{ route('pricing') }}" class="text-secondary-600 hover:text-primary transition-colors font-medium">Harga</a>
                    <a href="/#contact" class="text-secondary-600 hover:text-primary transition-colors font-medium">Kontak</a>
                </div>

                <!-- CTA Buttons -->
                <div class="hidden md:flex items-center gap-3">
                    <a href="{{ route('tenant.login') }}" class="px-4 py-2 text-secondary-700 font-medium hover:text-primary transition-colors">
                        Masuk
                    </a>
                    <a href="{{ route('tenant.login') }}" class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-primary to-primary-500 text-white font-medium rounded-xl hover:opacity-90 transition-all shadow-lg shadow-primary/25">
                        Coba Gratis
                    </a>
                </div>

                <!-- Mobile Menu Button -->
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden p-2 rounded-lg hover:bg-primary-50">
                    <svg x-show="!mobileMenuOpen" class="w-6 h-6 text-secondary-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <svg x-show="mobileMenuOpen" x-cloak class="w-6 h-6 text-secondary-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div x-show="mobileMenuOpen" x-cloak class="md:hidden bg-white border-t border-primary-100">
            <div class="px-4 py-4 space-y-3">
                <a href="/#features" class="block py-2 text-secondary-600 hover:text-primary font-medium">Fitur</a>
                <a href="/#modules" class="block py-2 text-secondary-600 hover:text-primary font-medium">Modul</a>
                <a href="{{ route('pricing') }}" class="block py-2 text-secondary-600 hover:text-primary font-medium">Harga</a>
                <a href="/#contact" class="block py-2 text-secondary-600 hover:text-primary font-medium">Kontak</a>
                <hr class="border-primary-100">
                <a href="{{ route('tenant.login') }}" class="block py-2 text-primary font-semibold">Masuk</a>
                <a href="{{ route('tenant.login') }}" class="block py-3 px-4 bg-gradient-to-r from-primary to-primary-500 text-white font-semibold rounded-xl text-center shadow-lg">Coba Gratis</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        {{ $slot }}
    </main>

    <!-- Footer - Premium Dark Style -->
    <footer class="bg-secondary-900 text-white relative overflow-hidden">
        <!-- Decorative gradient blur -->
        <div class="absolute top-0 right-0 w-96 h-96 bg-primary/20 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 bg-accent/10 rounded-full blur-3xl"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12">
                <!-- Brand -->
                <div class="lg:col-span-1">
                    <a href="/" class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 bg-gradient-to-br from-primary to-primary-500 rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <span class="text-xl font-bold">Ultimate POS</span>
                    </a>
                    <p class="text-secondary-400 mb-6">
                        Solusi POS premium untuk restoran, kafe, dan bisnis F&B. Kelola outlet, inventory, dan transaksi dengan mudah.
                    </p>
                    <div class="flex gap-3">
                        <a href="#" class="w-10 h-10 bg-secondary-800 hover:bg-primary rounded-xl flex items-center justify-center transition-all duration-300">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/></svg>
                        </a>
                        <a href="#" class="w-10 h-10 bg-secondary-800 hover:bg-primary rounded-xl flex items-center justify-center transition-all duration-300">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                        </a>
                        <a href="#" class="w-10 h-10 bg-secondary-800 hover:bg-primary rounded-xl flex items-center justify-center transition-all duration-300">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                        </a>
                    </div>
                </div>

                <!-- Product -->
                <div>
                    <h4 class="font-semibold text-lg mb-6">Produk</h4>
                    <ul class="space-y-3">
                        <li><a href="#features" class="text-secondary-400 hover:text-accent transition-colors">Fitur</a></li>
                        <li><a href="#modules" class="text-secondary-400 hover:text-accent transition-colors">Modul</a></li>
                        <li><a href="#pricing" class="text-secondary-400 hover:text-accent transition-colors">Harga</a></li>
                        <li><a href="#" class="text-secondary-400 hover:text-accent transition-colors">Integrasi</a></li>
                        <li><a href="#" class="text-secondary-400 hover:text-accent transition-colors">API</a></li>
                    </ul>
                </div>

                <!-- Support -->
                <div>
                    <h4 class="font-semibold text-lg mb-6">Dukungan</h4>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-secondary-400 hover:text-accent transition-colors">Dokumentasi</a></li>
                        <li><a href="#" class="text-secondary-400 hover:text-accent transition-colors">Panduan</a></li>
                        <li><a href="#" class="text-secondary-400 hover:text-accent transition-colors">FAQ</a></li>
                        <li><a href="#contact" class="text-secondary-400 hover:text-accent transition-colors">Hubungi Kami</a></li>
                    </ul>
                </div>

                <!-- Legal -->
                <div>
                    <h4 class="font-semibold text-lg mb-6">Legal</h4>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-secondary-400 hover:text-accent transition-colors">Syarat & Ketentuan</a></li>
                        <li><a href="#" class="text-secondary-400 hover:text-accent transition-colors">Kebijakan Privasi</a></li>
                        <li><a href="#" class="text-secondary-400 hover:text-accent transition-colors">Keamanan</a></li>
                    </ul>
                </div>
            </div>

            <hr class="border-secondary-800 my-12">

            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-secondary-500 text-sm">
                    &copy; {{ date('Y') }} Ultimate POS. All rights reserved.
                </p>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 bg-primary/20 text-primary-300 text-xs font-medium rounded-full">SSL Secured</span>
                    <span class="px-3 py-1 bg-accent/20 text-accent-300 text-xs font-medium rounded-full">PCI Compliant</span>
                </div>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
