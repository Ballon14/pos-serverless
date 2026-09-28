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
        <meta name="apple-mobile-web-app-title" content="Toko Mba Emi">
        <link rel="manifest" href="{{ asset('build/manifest.webmanifest') }}">
        <link rel="icon" href="{{ asset('logo.png') }}" type="image/png">
        <link rel="apple-touch-icon" href="{{ asset('logo.png') }}">

        <title>{{ config('app.name', 'Toko Mba Emi') }} - @yield('title', 'Dashboard')</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Icons -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans antialiased bg-slate-50 dark:bg-slate-900">
        <div class="min-h-screen flex">
            <!-- Sidebar -->
            @include('layouts.sidebar')

            <!-- Main Content -->
            <div class="flex-1 min-w-0 overflow-x-hidden lg:ml-64">
                <!-- Top Navbar -->
                @include('layouts.topbar')

                <!-- Flash Messages -->
                <x-flash-notifications />


                <!-- Page Content -->
                <main class="p-4 sm:p-6 lg:p-8">
                    @isset($header)
                        <div class="mb-6">
                            {{ $header }}
                        </div>
                    @endisset

                    {{ $slot }}
                </main>
            </div>
        </div>

        <!-- Mobile sidebar overlay -->
        <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-30 lg:hidden hidden" onclick="toggleSidebar()"></div>

        <!-- Custom Confirm Modal -->
        <div id="stockku-confirm-modal" x-data="confirmDialog()">
            <div x-show="open" x-cloak style="display:none" x-transition.opacity class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-end sm:items-center justify-center p-4" @keydown.escape.window="close()">
                <div @click="close()" class="absolute inset-0"></div>
                <div x-show="open" x-transition.scale.origin.bottom class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center" role="dialog" aria-modal="true">
                    <div class="mx-auto w-14 h-14 rounded-full flex items-center justify-center mb-4" :class="danger ? 'bg-red-100 dark:bg-red-900/30' : 'bg-indigo-100 dark:bg-indigo-900/30'">
                        <svg x-show="danger" class="w-7 h-7 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <svg x-show="!danger" class="w-7 h-7 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100 mb-2" x-text="title"></h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 whitespace-pre-line" x-text="message"></p>
                    <div class="flex gap-3">
                        <button type="button" @click="close()" class="flex-1 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 text-sm font-semibold hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">Batal</button>
                        <button type="button" @click="confirm()" :class="danger ? 'bg-red-600 hover:bg-red-700' : 'bg-indigo-600 hover:bg-indigo-700'" class="flex-1 py-2.5 rounded-xl text-white text-sm font-semibold shadow-lg transition-colors" x-text="confirmText"></button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function toggleSidebar() {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('sidebar-overlay');
                sidebar.classList.toggle('-translate-x-full');
                overlay.classList.toggle('hidden');
            }

            function resetSidebarScroll() {
                const nav = document.querySelector('#sidebar nav');
                if (nav) nav.scrollTop = 0;
            }

            function scrollSidebarToActive() {
                const active = document.querySelector('#sidebar nav .sidebar-active');
                if (active) active.scrollIntoView({ block: 'center' });
            }

            function syncSidebarPosition() {
                resetSidebarScroll();
                scrollSidebarToActive();
            }

            window.addEventListener('pageshow', syncSidebarPosition);
            window.addEventListener('load', syncSidebarPosition);
            window.setTimeout(syncSidebarPosition, 150);
        </script>

        @livewireScripts
        @stack('scripts')
    </body>
</html>
