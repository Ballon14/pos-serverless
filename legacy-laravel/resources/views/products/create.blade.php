<x-app-layout>
@section('title', 'Tambah Produk')
<x-slot name="header"><h2 class="text-2xl font-bold text-slate-800">Tambah Produk</h2></x-slot>
<div class="max-w-2xl">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
        <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nama Produk <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-200" required>
                    @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Kategori <span class="text-red-500">*</span></label>
                    <select name="category_id" id="category_id" class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-200" required>
                        <option value="">Pilih...</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">SKU <span class="text-slate-400 text-xs font-normal">(otomatis)</span></label>
                    <div class="relative">
                        <input type="text" name="sku" id="sku_input" value="{{ old('sku') }}" class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-200 pr-10 font-mono" placeholder="Pilih kategori dulu...">
                        <div id="sku_loading" class="absolute right-3 top-1/2 -translate-y-1/2 hidden">
                            <svg class="w-4 h-4 animate-spin text-indigo-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        </div>
                    </div>
                    @error('sku') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    <p class="text-xs text-slate-400 mt-1">Terisi otomatis dari kategori. Bisa diedit manual.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Satuan <span class="text-red-500">*</span></label>
                    <input type="text" name="satuan" value="{{ old('satuan', 'pcs') }}" class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-200" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Harga Beli <span class="text-red-500">*</span></label>
                    <input type="number" name="harga_beli" value="{{ old('harga_beli', 0) }}" min="0" class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-200" required>
                    @error('harga_beli') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Harga Jual <span class="text-red-500">*</span></label>
                    <input type="number" name="harga_jual" value="{{ old('harga_jual', 0) }}" min="0" class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-200" required>
                    @error('harga_jual') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <!-- Grosir Tiers (Multi-tier) -->
                <div class="md:col-span-2 border-t border-dashed border-slate-200 pt-4 mt-1" x-data="{
                    tiers: {{ json_encode(old('grosir_tiers', [])) }}
                }">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-xs font-semibold text-amber-600 uppercase tracking-wider">
                            <svg class="w-3.5 h-3.5 inline-block mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            Pengaturan Harga Grosir
                        </p>
                        @if(\App\Models\Setting::get('enable_multi_tier_grosir', '1') === '1')
                        <button type="button" @click="tiers.push({minimal_grosir: '', harga_grosir: ''})" class="text-xs font-medium text-indigo-600 hover:text-indigo-700 bg-indigo-50 hover:bg-indigo-100 px-2 py-1 rounded-md transition-colors">
                            + Tambah Level Grosir
                        </button>
                        @else
                        <button type="button" x-show="tiers.length < 1" @click="tiers.push({minimal_grosir: '', harga_grosir: ''})" class="text-xs font-medium text-indigo-600 hover:text-indigo-700 bg-indigo-50 hover:bg-indigo-100 px-2 py-1 rounded-md transition-colors">
                            + Tambah Harga Grosir
                        </button>
                        @endif
                    </div>

                    <template x-for="(tier, index) in tiers" :key="index">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-3 bg-slate-50 p-3 rounded-xl border border-slate-100 items-start">
                            <div class="md:col-span-5">
                                <label class="block text-xs font-medium text-slate-600 mb-1">Minimal Beli (Qty)</label>
                                <input type="number" x-model="tier.minimal_grosir" :name="`grosir_tiers[${index}][minimal_grosir]`" min="1" class="w-full rounded-lg border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-200" placeholder="Misal: 10" required>
                            </div>
                            <div class="md:col-span-6">
                                <label class="block text-xs font-medium text-slate-600 mb-1">Harga Grosir (per satuan)</label>
                                <input type="number" x-model="tier.harga_grosir" :name="`grosir_tiers[${index}][harga_grosir]`" min="0" class="w-full rounded-lg border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-200" placeholder="Misal: 3000" required>
                            </div>
                            <div class="md:col-span-1 pt-6 text-right">
                                <button type="button" @click="tiers.splice(index, 1)" class="p-2 rounded-lg text-red-400 hover:bg-red-100 hover:text-red-600 transition-colors" title="Hapus Level">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                    </template>
                    <p class="text-[11px] text-slate-400 mt-2">Tambahkan level sesuai kebutuhan (misal: Beli 10 harga Rp3.000, beli 40 harga Rp2.800). Kasir akan otomatis menyesuaikan harga berdasarkan jumlah terbanyak yang dicapai.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Stok Awal <span class="text-red-500">*</span></label>
                    <input type="number" name="stok" value="{{ old('stok', 0) }}" min="0" class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-200" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Minimum Stok <span class="text-red-500">*</span></label>
                    <input type="number" name="min_stok" value="{{ old('min_stok', 5) }}" min="0" class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-200" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Foto Produk</label>
                    <input type="file" name="foto" accept="image/*" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    @error('foto') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Deskripsi</label>
                    <textarea name="deskripsi" rows="3" class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-200">{{ old('deskripsi') }}</textarea>
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <a href="{{ route('products.index') }}" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-sm font-medium hover:bg-slate-200">Batal</a>
                <button type="submit" class="px-6 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl text-sm font-semibold shadow-lg shadow-indigo-500/30 hover:shadow-indigo-500/50 transition-all">Simpan</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const categorySelect = document.getElementById('category_id');
    const skuInput = document.getElementById('sku_input');
    const skuLoading = document.getElementById('sku_loading');
    let skuManuallyEdited = false;

    // Track if user manually edits SKU
    skuInput.addEventListener('input', function() {
        skuManuallyEdited = true;
    });

    categorySelect.addEventListener('change', function() {
        const categoryId = this.value;
        if (!categoryId) {
            if (!skuManuallyEdited) {
                skuInput.value = '';
                skuInput.placeholder = 'Pilih kategori dulu...';
            }
            return;
        }

        // Reset manual edit flag when category changes
        skuManuallyEdited = false;

        // Show loading
        skuLoading.classList.remove('hidden');
        skuInput.placeholder = 'Generating...';

        fetch(`/products/generate-sku/${categoryId}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        })
        .then(response => response.json())
        .then(data => {
            skuInput.value = data.sku;
            skuLoading.classList.add('hidden');
        })
        .catch(() => {
            skuLoading.classList.add('hidden');
            skuInput.placeholder = 'Gagal generate, isi manual';
        });
    });

    // Auto-generate on page load if category is pre-selected (e.g. old() value)
    if (categorySelect.value && !skuInput.value) {
        categorySelect.dispatchEvent(new Event('change'));
    }
});
</script>
@endpush
</x-app-layout>
