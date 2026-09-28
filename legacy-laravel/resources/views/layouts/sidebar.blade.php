@php
    $user = auth()->user();
    $currentRoute = request()->route()->getName() ?? '';
@endphp

<!-- Sidebar -->
<aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-gradient-to-b from-slate-900 via-slate-800 to-slate-900 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out flex flex-col h-full">
    <!-- Logo -->
    <div class="flex items-center gap-3 px-6 py-5 border-b border-slate-700/50 shrink-0">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center overflow-hidden">
            <img src="{{ asset('logo.png') }}?v=2" alt="Logo" class="w-full h-full object-contain">
        </div>
        <div class="flex-1">
            <h1 class="text-lg font-bold text-white tracking-tight">{{ config('app.name') }}</h1>
            <p class="text-xs text-slate-400">Manajemen Toko</p>
        </div>
        <button onclick="toggleSidebar()" class="lg:hidden p-2 rounded-lg text-slate-400 hover:bg-slate-700/50 hover:text-white transition-colors" title="Tutup menu">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <!-- Navigation (scrollable) -->
    <nav class="flex-1 mt-4 px-3 space-y-1 overflow-y-auto min-h-0 pb-4 [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
        <!-- Dashboard -->
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ Str::startsWith($currentRoute, 'dashboard') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30 sidebar-active' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            Dashboard
        </a>

        @if($user->hasRole(['admin', 'kasir']))
        <!-- POS -->
        <a href="{{ route('pos') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ $currentRoute === 'pos' ? 'bg-emerald-600/20 text-emerald-300 border border-emerald-500/30 sidebar-active' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            Kasir (POS)
        </a>
        @endif

        @if($user->hasRole('admin'))
        <!-- Master Data Section -->
        <div class="pt-4">
            <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Master Data</p>
        </div>

        <a href="{{ route('categories.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ Str::startsWith($currentRoute, 'categories') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30 sidebar-active' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
            Kategori
        </a>

        <a href="{{ route('products.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ Str::startsWith($currentRoute, 'products') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30 sidebar-active' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            Produk
        </a>

        <a href="{{ route('suppliers.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ Str::startsWith($currentRoute, 'suppliers') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30 sidebar-active' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            Supplier
        </a>


        @endif

        @if($user->hasRole(['admin', 'kasir']))
        <!-- Transaksi Section -->
        <div class="pt-4">
            <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Transaksi</p>
        </div>

        <a href="{{ route('sales.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ Str::startsWith($currentRoute, 'sales') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30 sidebar-active' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
            Riwayat Penjualan
        </a>

        @if($user->hasRole('admin'))
        <a href="{{ route('sale-returns.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ Str::startsWith($currentRoute, 'sale-returns') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30 sidebar-active' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"/></svg>
            Retur Penjualan
        </a>

        <a href="{{ route('purchases.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ Str::startsWith($currentRoute, 'purchases') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30 sidebar-active' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
            Pembelian
        </a>

        <!-- Stok Section -->
        <div class="pt-4">
            <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Stok</p>
        </div>

        <a href="{{ route('stock.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ $currentRoute === 'stock.index' ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30 sidebar-active' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Kartu Stok
        </a>

        <a href="{{ route('stock.low') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ $currentRoute === 'stock.low' ? 'bg-amber-600/20 text-amber-300 border border-amber-500/30 sidebar-active' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
            Stok Menipis
            @php $lowStockCount = \App\Models\Product::whereColumn('stok', '<=', 'min_stok')->where('is_active', true)->count(); @endphp
            @if($lowStockCount > 0)
            <span class="ml-auto bg-amber-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">{{ $lowStockCount }}</span>
            @endif
        </a>
        @endif
        @endif



        @if($user->hasRole(['admin', 'kasir']))
        <!-- Laporan Section -->
        <div class="pt-4">
            <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Laporan</p>
        </div>

        <a href="{{ route('reports.sales') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ $currentRoute === 'reports.sales' ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30 sidebar-active' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            Laporan Penjualan
        </a>

        @if($user->hasRole(['admin']))
        <a href="{{ route('reports.profit-loss') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ $currentRoute === 'reports.profit-loss' ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30 sidebar-active' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Laba Rugi
        </a>

        <a href="{{ route('reports.stock') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ $currentRoute === 'reports.stock' ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30 sidebar-active' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
            Mutasi Stok
        </a>



        <a href="{{ route('reports.price-change') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ $currentRoute === 'reports.price-change' ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30 sidebar-active' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 0v4"/></svg>
            Perubahan Harga
        </a>
        @endif
        @endif

        @if($user->hasRole('admin'))
        <!-- Sistem Section -->
        <div class="pt-4">
            <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Sistem</p>
        </div>

        <a href="{{ route('activity-logs.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ Str::startsWith($currentRoute, 'activity-logs') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30 sidebar-active' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            Log Aktivitas
        </a>

        <a href="{{ route('settings.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ Str::startsWith($currentRoute, 'settings') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30 sidebar-active' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Pengaturan Sistem
        </a>
        @endif

        <!-- Akun Section -->
        <div class="pt-4">
            <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Akun</p>
        </div>

        <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ $currentRoute === 'profile.edit' ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30 sidebar-active' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            Pengaturan Profil
        </a>
    </nav>

    <!-- User Info at Bottom -->
    <div class="shrink-0 p-4 border-t border-slate-700/50">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-gradient-to-br from-indigo-400 to-purple-500 rounded-lg flex items-center justify-center text-white text-sm font-bold">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-white truncate">{{ $user->name }}</p>
                <p class="text-xs text-slate-400 capitalize">{{ $user->roles->first()->name ?? 'User' }}</p>
            </div>
        </div>
    </div>
</aside>
