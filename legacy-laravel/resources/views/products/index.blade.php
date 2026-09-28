<x-app-layout>
@section('title', 'Produk')
<x-slot name="header">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <h2 class="text-2xl font-bold text-slate-800">Produk</h2>
        <div class="flex items-center gap-2">
            <!-- Export Button -->
            <a href="{{ route('products.export') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-500 text-white rounded-xl text-sm font-semibold shadow-sm hover:bg-emerald-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" class="hidden"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                Export Excel
            </a>
            
            <!-- Import Button (triggers modal) -->
            <button type="button" x-data="" @click="$dispatch('open-modal', 'import-product')" class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 text-white rounded-xl text-sm font-semibold shadow-sm hover:bg-amber-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                Import
            </button>

            <!-- Add Button -->
            <a href="{{ route('products.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl text-sm font-semibold shadow-lg shadow-indigo-500/30 hover:shadow-indigo-500/50 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Tambah Produk
            </a>
        </div>
    </div>
</x-slot>

<!-- Filter -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="text-xs font-medium text-slate-500">Cari</label>
            <input type="text" name="search" value="{{ $search }}" placeholder="Nama / SKU" class="w-full mt-1 rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-200">
        </div>
        <div class="w-48">
            <label class="text-xs font-medium text-slate-500">Kategori</label>
            <select name="category_id" class="w-full mt-1 rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-200">
                <option value="">Semua</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-xl text-sm font-medium">Filter</button>
        <a href="{{ route('products.index') }}" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-xl text-sm font-medium">Reset</a>
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <!-- Mobile: card list -->
    <div class="md:hidden divide-y divide-slate-100">
        @forelse($products as $product)
        <div class="p-4">
            <div class="flex items-center gap-3 mb-3">
                @if($product->foto)
                <img src="{{ asset('storage/' . $product->foto) }}" class="w-11 h-11 rounded-lg object-cover shrink-0" alt="">
                @else
                <div class="w-11 h-11 rounded-lg bg-slate-100 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                @endif
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-slate-700 truncate">{{ $product->name }}</p>
                    <p class="font-mono text-xs text-slate-400 mt-0.5">{{ $product->sku }} · {{ $product->category->name }}</p>
                </div>
                <span class="shrink-0 px-2 py-0.5 rounded-full text-xs font-bold {{ $product->isLowStock() ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">{{ $product->stok }}</span>
            </div>
            <div class="flex items-center justify-between">
                <div class="text-xs text-slate-500">
                    <p>Beli: <span class="font-semibold text-slate-700">Rp {{ number_format($product->harga_beli, 0, ',', '.') }}</span></p>
                    <p>Jual: <span class="font-semibold text-slate-700">Rp {{ number_format($product->harga_jual, 0, ',', '.') }}</span></p>
                </div>
                <div class="flex items-center gap-1">
                    <a href="{{ route('products.show', $product) }}" class="p-1.5 rounded-lg text-slate-400 hover:bg-indigo-50 hover:text-indigo-600" title="Detail"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></a>
                    <a href="{{ route('products.edit', $product) }}" class="p-1.5 rounded-lg text-slate-400 hover:bg-indigo-50 hover:text-indigo-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></a>
                    <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirmForm(this, 'Yakin hapus produk ini?')">
                        @csrf @method('DELETE')
                        <button class="p-1.5 rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="py-8 text-center text-slate-400">Belum ada produk.</div>
        @endforelse
    </div>

    <!-- Desktop: table -->
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="text-left py-3 px-4 font-semibold text-slate-600">#</th>
                    <th class="text-left py-3 px-4 font-semibold text-slate-600">Produk</th>
                    <th class="text-left py-3 px-4 font-semibold text-slate-600">SKU</th>
                    <th class="text-left py-3 px-4 font-semibold text-slate-600">Kategori</th>
                    <th class="text-right py-3 px-4 font-semibold text-slate-600">Harga Beli</th>
                    <th class="text-right py-3 px-4 font-semibold text-slate-600">Harga Jual</th>
                    <th class="text-right py-3 px-4 font-semibold text-slate-600">Harga Grosir</th>
                    <th class="text-center py-3 px-4 font-semibold text-slate-600">Stok</th>
                    <th class="text-center py-3 px-4 font-semibold text-slate-600">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $i => $product)
                <tr class="border-b border-slate-50 hover:bg-slate-50/50">
                    <td class="py-3 px-4 text-slate-500">{{ $products->firstItem() + $i }}</td>
                    <td class="py-3 px-4">
                        <div class="flex items-center gap-3">
                            @if($product->foto)
                            <img src="{{ asset('storage/' . $product->foto) }}" class="w-10 h-10 rounded-lg object-cover" alt="">
                            @else
                            <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </div>
                            @endif
                            <span class="font-medium text-slate-700">{{ $product->name }}</span>
                        </div>
                    </td>
                    <td class="py-3 px-4 text-slate-500 font-mono text-xs">{{ $product->sku }}</td>
                    <td class="py-3 px-4 text-slate-500">{{ $product->category->name }}</td>
                    <td class="py-3 px-4 text-right text-slate-600">Rp {{ number_format($product->harga_beli, 0, ',', '.') }}</td>
                    <td class="py-3 px-4 text-right font-semibold text-slate-700">Rp {{ number_format($product->harga_jual, 0, ',', '.') }}</td>
                    <td class="py-3 px-4 text-right text-slate-600">
                        @if(is_array($product->grosir_tiers) && count($product->grosir_tiers) > 0)
                            <div class="space-y-1">
                                @foreach(collect($product->grosir_tiers)->sortBy('minimal_grosir')->take(2) as $tier)
                                    <div>
                                        <span class="font-semibold text-amber-700 text-xs">Rp {{ number_format($tier['harga_grosir'], 0, ',', '.') }}</span>
                                        <span class="text-[10px] text-slate-400"> (≥ {{ $tier['minimal_grosir'] }})</span>
                                    </div>
                                @endforeach
                                @if(count($product->grosir_tiers) > 2)
                                    <div class="text-[10px] text-indigo-500 font-medium">+ {{ count($product->grosir_tiers) - 2 }} level lainnya</div>
                                @endif
                            </div>
                        @else
                            <span class="text-slate-300">—</span>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-center">
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $product->isLowStock() ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">{{ $product->stok }}</span>
                    </td>
                    <td class="py-3 px-4 text-center">
                        <div class="flex items-center justify-center gap-1">
                            <a href="{{ route('products.show', $product) }}" class="p-1.5 rounded-lg text-slate-400 hover:bg-indigo-50 hover:text-indigo-600 transition-colors" title="Detail">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                            <a href="{{ route('products.edit', $product) }}" class="p-1.5 rounded-lg text-slate-400 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirmForm(this, 'Yakin hapus produk ini?')">
                                @csrf @method('DELETE')
                                <button class="p-1.5 rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="py-8 text-center text-slate-400">Belum ada produk.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 border-t border-slate-100">{{ $products->appends(request()->query())->links() }}</div>
</div>

<!-- Import Modal -->
<div x-data="{ open: false }" @open-modal.window="if ($event.detail === 'import-product') open = true">
    <!-- Backdrop -->
    <div x-show="open" style="display: none;" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50" x-transition.opacity></div>

    <!-- Modal Panel -->
    <div x-show="open" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden relative" @click.away="open = false">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <h3 class="text-base font-bold text-slate-800">Import Produk</h3>
                <button @click="open = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form action="{{ route('products.import') }}" method="POST" enctype="multipart/form-data" class="p-5">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1">File Excel/CSV</label>
                    <input type="file" name="file" accept=".xlsx,.csv" required class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition-colors">
                    
                    <div class="mt-4 p-4 bg-slate-50 rounded-xl border border-slate-100" x-data="{
                        selectedCat: '',
                        get downloadUrl() {
                            return '{{ route('products.import-template') }}' + (this.selectedCat ? '?category_id=' + this.selectedCat : '');
                        }
                    }">
                        <label class="block text-xs font-semibold text-slate-700 mb-2">Unduh Template Berdasarkan Kategori</label>
                        <div class="flex gap-2">
                            <select x-model="selectedCat" class="flex-1 rounded-lg border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-200">
                                <option value="">Pilih Kategori...</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            <a :href="downloadUrl" class="px-3 py-2 bg-white border border-slate-200 rounded-lg text-indigo-600 hover:bg-indigo-50 hover:border-indigo-200 transition-colors flex items-center justify-center shrink-0" title="Unduh Template">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            </a>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-2 leading-relaxed">Template akan disesuaikan dengan kategori yang Anda pilih (Kategori terisi otomatis di beberapa baris awal).</p>
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="open = false" class="px-4 py-2 rounded-xl text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition-colors">Import Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

</x-app-layout>
