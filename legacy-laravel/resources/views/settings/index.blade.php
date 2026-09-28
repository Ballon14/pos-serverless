<x-app-layout>
@section('title', 'Pengaturan')
<x-slot name="header">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Pengaturan</h2>
            <p class="text-sm text-slate-500 mt-1">Kelola data sistem dan cadangkan database</p>
        </div>
    </div>
</x-slot>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Configuration Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden col-span-1 md:col-span-2">
        <div class="p-6 border-b border-slate-100 bg-slate-50/50">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-800">Konfigurasi Sistem</h3>
                    <p class="text-sm text-slate-500 mt-0.5">Atur fitur dan fungsionalitas aplikasi</p>
                </div>
            </div>
        </div>
        <div class="p-6">
            <form action="{{ route('settings.update') }}" method="POST">
                @csrf
                <div class="flex items-center justify-between gap-4 p-4 rounded-xl border border-slate-100 bg-white">
                    <div>
                        <h4 class="text-sm font-semibold text-slate-800">Aktifkan Grosir Multi-Tingkat (Multi-Tier)</h4>
                        <p class="text-xs text-slate-500 mt-1">Jika aktif, Anda dapat mengatur banyak tingkatan harga grosir (misal per 10pcs, 40pcs) untuk satu produk.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer shrink-0">
                        <input type="checkbox" name="enable_multi_tier_grosir" value="1" class="sr-only peer" {{ ($settings['enable_multi_tier_grosir'] ?? '1') === '1' ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                    </label>
                </div>
                
                <div class="mt-6 flex justify-end">
                    <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-semibold shadow-sm hover:bg-indigo-700 transition-colors">
                        Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Backup Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-6 border-b border-slate-100 bg-slate-50/50">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" class="hidden"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-800">Backup Database</h3>
                    <p class="text-sm text-slate-500 mt-0.5">Unduh semua data ke dalam file .sql</p>
                </div>
            </div>
        </div>
        <div class="p-6">
            <p class="text-sm text-slate-600 mb-6">
                Fitur ini akan mengekspor seluruh tabel di dalam sistem (Produk, Penjualan, Absensi, Pengguna, dll) menjadi satu file SQL atau ZIP berisi CSV. Simpan file ini di tempat yang aman sebagai cadangan data Anda.
            </p>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('settings.backup') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-500 text-white rounded-xl text-sm font-semibold shadow-sm hover:bg-emerald-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Backup (.sql)
                </a>
                <a href="{{ route('settings.backup-csv') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-teal-500 text-white rounded-xl text-sm font-semibold shadow-sm hover:bg-teal-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Backup CSV (.zip)
                </a>
            </div>
        </div>
    </div>

    <!-- Restore Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden border-t-4 border-t-red-500">
        <div class="p-6 border-b border-slate-100 bg-slate-50/50">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-800">Restore Database</h3>
                    <p class="text-sm text-slate-500 mt-0.5">Pulihkan data dari file .sql atau .zip</p>
                </div>
            </div>
        </div>
        <div class="p-6">
            <div class="mb-6 p-4 bg-red-50 rounded-xl border border-red-100">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    <p class="text-sm text-red-800 font-medium leading-relaxed">
                        <strong class="font-bold">PERHATIAN:</strong> Proses ini akan menghapus semua data yang ada saat ini dan menimpanya dengan data dari file backup. Pastikan Anda mengunggah file yang benar!
                    </p>
                </div>
            </div>

            <!-- Tabs for Restore -->
            <div x-data="{ tab: 'sql' }">
                <div class="flex border-b border-slate-200 mb-4">
                    <button @click="tab = 'sql'" :class="tab === 'sql' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'" class="py-2 px-4 border-b-2 font-medium text-sm transition-colors">Restore .sql</button>
                    <button @click="tab = 'csv'" :class="tab === 'csv' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'" class="py-2 px-4 border-b-2 font-medium text-sm transition-colors">Restore .zip (CSV)</button>
                </div>

                <!-- Form SQL -->
                <form x-show="tab === 'sql'" action="{{ route('settings.restore') }}" method="POST" enctype="multipart/form-data" onsubmit="return confirmForm(this, 'PERINGATAN: Semua data saat ini akan dihapus dan ditimpa dengan data SQL! Lanjutkan?')">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 mb-2">Upload File Backup (.sql)</label>
                        <input type="file" name="backup_file" accept=".sql" required class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100 transition-colors">
                        @error('backup_file')
                            <p class="text-xs text-red-500 mt-2">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-500 text-white rounded-xl text-sm font-semibold shadow-sm hover:bg-red-600 transition-colors w-full justify-center sm:w-auto">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        Jalankan Restore (.sql)
                    </button>
                </form>

                <!-- Form CSV -->
                <form x-show="tab === 'csv'" style="display: none;" action="{{ route('settings.restore-csv') }}" method="POST" enctype="multipart/form-data" onsubmit="return confirmForm(this, 'PERINGATAN: Semua data saat ini akan dihapus dan ditimpa dengan data CSV! Lanjutkan?')">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 mb-2">Upload File Backup CSV (.zip)</label>
                        <input type="file" name="backup_zip" accept=".zip" required class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100 transition-colors">
                        @error('backup_zip')
                            <p class="text-xs text-red-500 mt-2">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-amber-500 text-white rounded-xl text-sm font-semibold shadow-sm hover:bg-amber-600 transition-colors w-full justify-center sm:w-auto">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        Jalankan Restore (.zip)
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
