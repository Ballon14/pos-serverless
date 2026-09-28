<!-- Top Bar -->
<header class="sticky top-0 z-20 bg-white/80 backdrop-blur-lg border-b border-slate-200/60">
    <div class="flex items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
        <!-- Mobile Menu Button -->
        <button onclick="toggleSidebar()" class="lg:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>

        <!-- Page Title -->
        <div class="hidden lg:block">
            <h2 class="text-lg font-semibold text-slate-800">@yield('title', 'Dashboard')</h2>
        </div>

        <!-- Right Side -->
        <div class="flex items-center gap-3">
            <!-- Current Time -->
            <div class="hidden sm:flex items-center gap-2 text-sm text-slate-500">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span id="current-time"></span>
            </div>

            <!-- User Dropdown -->
            <div class="relative" x-data="{ open: false }">
                <div class="flex items-center gap-3">
                    <a href="{{ route('profile.edit') }}" class="text-sm text-slate-600 hover:text-slate-800 transition-colors hidden sm:block">
                        {{ Auth::user()->name }}
                    </a>
                    <form method="POST" action="{{ route('logout') }}" onsubmit="return confirmForm(this, 'Yakin ingin keluar dari aplikasi?', { title: 'Logout', confirmText: 'Ya, Keluar', danger: true })">
                        @csrf
                        <button type="submit" class="p-2 rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-500 transition-colors" title="Logout">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
    function updateTime() {
        const now = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false };
        const el = document.getElementById('current-time');
        if (el) el.textContent = now.toLocaleDateString('id-ID', options);
    }
    updateTime();
    setInterval(updateTime, 1000);
</script>
