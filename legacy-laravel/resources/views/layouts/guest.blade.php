<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <meta name="theme-color" content="#6366f1">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="{{ config('app.name', 'Toko Mba Emi') }}">
        <link rel="manifest" href="{{ asset('build/manifest.webmanifest') }}">
        <link rel="icon" href="{{ asset('logo.png') }}?v=2" type="image/png">
        <link rel="apple-touch-icon" href="{{ asset('logo.png') }}?v=2">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Icons -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        <div class="relative min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 overflow-hidden bg-slate-950">
            <!-- Background gradient -->
            <div class="absolute inset-0 bg-gradient-to-br from-indigo-950 via-slate-900 to-purple-950"></div>

            <!-- Decorative blurred blobs -->
            <div class="absolute -top-32 -left-32 w-[480px] h-[480px] bg-indigo-600 rounded-full blur-3xl opacity-30 animate-pulse"></div>
            <div class="absolute -bottom-40 -right-40 w-[520px] h-[520px] bg-purple-600 rounded-full blur-3xl opacity-30"></div>
            <div class="absolute top-1/3 right-1/4 w-72 h-72 bg-purple-500 rounded-full blur-3xl opacity-20"></div>
            <div class="absolute bottom-1/4 left-1/5 w-64 h-64 bg-indigo-500 rounded-full blur-3xl opacity-20"></div>

            <!-- Subtle grid overlay -->
            <div class="absolute inset-0 opacity-[0.05]" style="background-image: linear-gradient(to right, rgba(255,255,255,.6) 1px, transparent 1px), linear-gradient(to bottom, rgba(255,255,255,.6) 1px, transparent 1px); background-size: 40px 40px;"></div>

            <!-- Content -->
            <div class="relative z-10 flex flex-col items-center px-4">
                <a href="/" class="mb-8 block">
                    <div class="w-20 h-20 rounded-2xl bg-slate-900 shadow-xl shadow-black/20 ring-1 ring-white/20 flex items-center justify-center overflow-hidden transform transition duration-300 hover:scale-105">
                        <img src="{{ asset('logo.png') }}?v=2" alt="Logo" class="w-full h-full object-contain p-2">
                    </div>
                </a>

                <div class="w-full sm:max-w-md px-6 sm:px-8 py-8 bg-white/95 backdrop-blur-xl shadow-2xl shadow-black/40 ring-1 ring-white/60 sm:rounded-3xl rounded-2xl">
                    {{ $slot }}
                </div>

                <p class="mt-6 text-sm text-slate-400/80">© {{ date('Y') }} {{ config('app.name') }} — Sistem Manajemen Stok</p>
            </div>
        </div>
    </body>
</html>
