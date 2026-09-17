<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'TheMerchant') }} - @yield('title', 'Marketplace de Cosméticos e Serviços')</title>

    <!-- Google Fonts & Tailwind CDN / Vite -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="h-full flex flex-col antialiased selection:bg-brand-500 selection:text-white">
    <!-- Navbar -->
    @include('layouts.navigation')

    <!-- Flash Messages -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full mt-4">
        @if (session('success'))
            <div class="p-4 rounded-xl bg-emerald-950/80 border border-emerald-500/40 text-emerald-300 text-sm flex items-center gap-2">
                <span>✅</span> {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="p-4 rounded-xl bg-rose-950/80 border border-rose-500/40 text-rose-300 text-sm flex items-center gap-2">
                <span>⚠️</span> {{ session('error') }}
            </div>
        @endif
        @if (session('info'))
            <div class="p-4 rounded-xl bg-sky-950/80 border border-sky-500/40 text-sky-300 text-sm flex items-center gap-2">
                <span>ℹ️</span> {{ session('info') }}
            </div>
        @endif
    </div>

    <!-- Main Content -->
    <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full py-8">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800/80 bg-slate-900/50 py-8 mt-12 text-center text-xs text-slate-400">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center gap-4">
            <div>
                <span class="font-bold text-white tracking-wider">TheMerchant</span> &copy; {{ date('Y') }} — FATEC PG (LES III)
            </div>
            <div class="flex gap-6">
                <a href="{{ route('home') }}" class="hover:text-slate-200 transition">Início</a>
                <a href="{{ route('listings.index') }}" class="hover:text-slate-200 transition">Catálogo</a>
                <a href="https://github.com/IgorMarcoli/TheMerchant" target="_blank" class="hover:text-slate-200 transition">GitHub</a>
            </div>
        </div>
    </footer>
</body>
</html>
