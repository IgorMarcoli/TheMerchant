<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'TheMerchant') }} - @yield('title', 'Marketplace de Cosméticos e Serviços')</title>

    <link rel="stylesheet" href="{{ asset('css/theme.css') }}">

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
                        // Shared CSS tokens keep Tailwind pages and the storefront in sync.
                        brand: Object.fromEntries(
                            [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950].map(
                                shade => [shade, `rgb(var(--tm-brand-${shade}) / <alpha-value>)`]
                            )
                        ),
                        slate: Object.fromEntries(
                            [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950].map(
                                shade => [shade, `rgb(var(--tm-neutral-${shade}) / <alpha-value>)`]
                            )
                        ),
                    }
                }
            }
        }
    </script>
    @stack('styles')
</head>
<body class="min-h-screen flex flex-col antialiased selection:bg-brand-600 selection:text-slate-950 @yield('body-class')">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:z-[100] focus:bg-white focus:text-black focus:p-4">Pular para o conteúdo</a>
    <!-- Navbar -->
    @hasSection('navigation')
        @yield('navigation')
    @else
        @include('layouts.navigation')
    @endif

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
    <main id="main-content" class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full py-8">
        @yield('content')
    </main>

    <x-marketplace-footer />
</body>
</html>
