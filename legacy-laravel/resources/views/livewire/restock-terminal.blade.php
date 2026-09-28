<div>
    {{-- Success notification --}}
    @if(session()->has('restock-success'))
    <div x-data="{ open: true }" x-init="setTimeout(() => open = false, 3000)" @keydown.escape.window="open = false" x-show="open" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/50">
        <div class="w-full max-w-sm bg-white rounded-3xl shadow-2xl shadow-slate-900/30 overflow-hidden">
            <div class="p-6 text-center">
                <div class="mx-auto w-14 h-14 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-4">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                </div>
                <h3 class="text-lg font-bold text-slate-800 mb-1">Pembelian Berhasil</h3>
                <p class="text-sm text-slate-500 leading-relaxed">{{ session('restock-success') }}</p>
                @if($lastPurchaseId)
                <a href="{{ route('purchases.show', $lastPurchaseId) }}" class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-700 hover:text-emerald-900 underline">Lihat Detail →</a>
                @endif
            </div>
            <div class="px-6 pb-6">
                <button type="button" @click="open = false" class="w-full py-2.5 rounded-xl font-semibold text-white shadow-lg bg-emerald-600 shadow-emerald-500/30 transition-all hover:brightness-110 active:scale-[0.98]">OK</button>
            </div>
        </div>
    </div>
    @endif

    {{-- Error notification --}}
    @if(session()->has('restock-error'))
    <div class="mb-4 rounded-xl bg-red-50 border border-red-200 p-4">
        <span class="text-sm font-medium text-red-800">{{ session('restock-error') }}</span>
    </div>
    @endif

    {{-- ===================== Restock Terminal ===================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:h-[calc(100vh-12rem)]">
        {{-- Left: Product Search --}}
        <div class="lg:col-span-1 bg-white rounded-2xl shadow-sm border border-slate-100 flex flex-col overflow-hidden">
            <div class="p-4 border-b border-slate-100">
                <h3 class="text-lg font-semibold text-slate-800 mb-3">Cari Produk</h3>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input wire:model.live.debounce.300ms="search" wire:keydown.enter="addBySearchEnter" type="text" placeholder="Nama produk, kategori, atau SKU..." class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 text-sm transition-all">
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-4 space-y-2 max-h-[45vh] lg:max-h-none">
                @forelse($products as $product)
                <button wire:click="addToCart({{ $product->id }})" class="w-full text-left p-3 rounded-xl border border-slate-100 hover:border-indigo-300 hover:bg-indigo-50/50 transition-all group">
                    <div class="flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-700 group-hover:text-indigo-700 truncate">{{ $product->name }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">{{ $product->sku }} · Stok: {{ $product->stok }} {{ $product->satuan }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-xs text-slate-400">Harga Beli</p>
                            <p class="text-sm font-bold text-indigo-600">Rp {{ number_format($product->harga_beli, 0, ',', '.') }}</p>
                            <svg class="w-5 h-5 text-slate-300 group-hover:text-indigo-500 ml-auto mt-1 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        </div>
                    </div>
                </button>
                @empty
                <div class="text-center py-8">
                    <svg class="w-12 h-12 text-slate-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-sm text-slate-400">Produk tidak ditemukan</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Right: Cart + Purchase Info --}}
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-100 flex flex-col overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">
                    <svg class="w-5 h-5 inline-block mr-1 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    Daftar Item Restock
                    @if(count($cart) > 0)
                    <span class="ml-2 bg-indigo-100 text-indigo-700 text-xs font-bold px-2 py-0.5 rounded-full">{{ count($cart) }} item</span>
                    @endif
                </h3>
                @if(count($cart) > 0)
                <button wire:click="clearCart" class="text-sm text-red-500 hover:text-red-700 font-medium">Hapus Semua</button>
                @endif
            </div>

            {{-- Purchase Info Header --}}
            <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Supplier <span class="text-red-500">*</span></label>
                        <select wire:model.live="supplierId" class="w-full rounded-xl border-slate-200 text-sm py-2 focus:border-indigo-500 focus:ring-indigo-200" required>
                            <option value="">Pilih Supplier...</option>
                            @foreach($suppliers as $sup)
                            <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Tanggal <span class="text-red-500">*</span></label>
                        <input type="date" wire:model.live="tanggal" class="w-full rounded-xl border-slate-200 text-sm py-2 focus:border-indigo-500 focus:ring-indigo-200" required>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Keterangan</label>
                        <input type="text" wire:model.live="keterangan" placeholder="No. ref / surat jalan..." class="w-full rounded-xl border-slate-200 text-sm py-2 focus:border-indigo-500 focus:ring-indigo-200">
                    </div>
                </div>
                <div class="mt-3" x-data="{ preview: null }">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">
                        Foto Nota
                        <span class="text-xs text-slate-400 font-normal">(maks 2MB)</span>
                    </label>
                    <div class="flex items-center gap-3">
                        <input type="file" wire:model="fotoNota" accept="image/jpeg,image/png,image/webp"
                            class="flex-1 rounded-xl border-slate-200 text-sm py-1.5 focus:border-indigo-500 focus:ring-indigo-200 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100"
                            @change="if ($event.target.files[0]) { const r = new FileReader(); r.onload = e => preview = e.target.result; r.readAsDataURL($event.target.files[0]); } else { preview = null; }">
                        <template x-if="preview">
                            <div class="relative shrink-0">
                                <img :src="preview" alt="Preview Nota" class="h-10 w-10 rounded-lg border border-slate-200 shadow-sm object-cover">
                                <button type="button" @click="preview = null; $el.closest('[x-data]').querySelector('input[type=file]').value = ''; $wire.set('fotoNota', null)"
                                    class="absolute -top-1.5 -right-1.5 bg-red-500 text-white rounded-full w-4 h-4 flex items-center justify-center text-[10px] hover:bg-red-600 shadow">×</button>
                            </div>
                        </template>
                    </div>
                    @error('fotoNota') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Mobile cart cards --}}
            <div class="lg:hidden flex-1 overflow-y-auto max-h-[50vh] lg:max-h-none">
                @if(count($cart) > 0)
                <div class="divide-y divide-slate-100">
                    @foreach($cart as $key => $item)
                    <div class="p-4 space-y-3" wire:key="{{ $key }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-medium text-slate-700 truncate">{{ $item['name'] }}</p>
                            </div>
                            <button wire:click="removeItem('{{ $key }}')" class="p-1.5 rounded-lg text-red-400 hover:bg-red-50 hover:text-red-600 transition-colors shrink-0">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            </button>
                        </div>
                        <div class="flex items-end gap-3">
                            <div class="w-20">
                                <label class="block text-xs text-slate-500 mb-1">Qty</label>
                                <div class="flex items-center gap-1">
                                    <button wire:click="updateQty('{{ $key }}', {{ max(0, $item['qty'] - 1) }})" class="w-7 h-7 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors text-sm leading-none">−</button>
                                    <input type="number" wire:change="updateQty('{{ $key }}', $event.target.value)" value="{{ $item['qty'] }}" min="1" class="w-12 text-center rounded-lg border border-slate-200 py-1 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-200">
                                    <button wire:click="updateQty('{{ $key }}', {{ $item['qty'] + 1 }})" class="w-7 h-7 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors text-sm leading-none">+</button>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <label class="block text-xs text-slate-500 mb-1">Harga Beli</label>
                                <input type="number" wire:change="updateHarga('{{ $key }}', $event.target.value)" value="{{ $item['harga'] }}" min="0" class="w-full text-right rounded-lg border-slate-200 py-1 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-200">
                            </div>
                            <div class="shrink-0 text-right">
                                <label class="block text-xs text-slate-500 mb-1">Subtotal</label>
                                <p class="font-semibold text-slate-800 text-sm">Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="flex flex-col items-center justify-center h-full text-center py-12">
                    <svg class="w-16 h-16 text-slate-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    <p class="text-slate-400 font-medium">Belum ada item</p>
                    <p class="text-slate-300 text-sm mt-1">Cari dan tambahkan produk untuk restock</p>
                </div>
                @endif
            </div>

            {{-- Desktop cart table --}}
            <div class="hidden lg:block flex-1 overflow-y-auto">
                @if(count($cart) > 0)
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 sticky top-0">
                        <tr>
                            <th class="text-left py-3 px-4 font-medium text-slate-500">Produk</th>
                            <th class="text-center py-3 px-4 font-medium text-slate-500 w-28">Qty</th>
                            <th class="text-right py-3 px-4 font-medium text-slate-500 w-40">Harga Beli</th>
                            <th class="text-right py-3 px-4 font-medium text-slate-500 w-36">Subtotal</th>
                            <th class="w-10"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cart as $key => $item)
                        <tr class="border-b border-slate-50 hover:bg-slate-50/50" wire:key="{{ $key }}">
                            <td class="py-3 px-4">
                                <p class="font-medium text-slate-700">{{ $item['name'] }}</p>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button wire:click="updateQty('{{ $key }}', {{ max(0, $item['qty'] - 1) }})" class="w-7 h-7 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 transition-colors text-sm leading-none">−</button>
                                    <input type="number" wire:change="updateQty('{{ $key }}', $event.target.value)" value="{{ $item['qty'] }}" min="1" class="w-14 text-center rounded-lg border border-slate-200 py-1 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-200">
                                    <button wire:click="updateQty('{{ $key }}', {{ $item['qty'] + 1 }})" class="w-7 h-7 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 transition-colors text-sm leading-none">+</button>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="relative">
                                    <input type="number" wire:change="updateHarga('{{ $key }}', $event.target.value)" value="{{ $item['harga'] }}" min="0" class="w-full text-right rounded-lg border border-slate-200 py-1.5 pl-7 pr-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-200">
                                    <span class="absolute left-2 top-1/2 -translate-y-1/2 text-xs text-slate-400">Rp</span>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-right font-semibold text-slate-800">Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</td>
                            <td class="py-3 px-2">
                                <button wire:click="removeItem('{{ $key }}')" class="p-1 rounded-lg text-red-400 hover:bg-red-50 hover:text-red-600 transition-colors">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <div class="flex flex-col items-center justify-center h-full text-center py-12">
                    <svg class="w-16 h-16 text-slate-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    <p class="text-slate-400 font-medium">Belum ada item</p>
                    <p class="text-slate-300 text-sm mt-1">Cari dan tambahkan produk untuk restock</p>
                </div>
                @endif
            </div>

            {{-- Footer: Total & Submit --}}
            @if(count($cart) > 0)
            <div class="border-t border-slate-200 p-4 bg-slate-50/50" x-data="{ showConfirm: false }">
                <div class="mb-4 bg-white p-3 rounded-xl border border-slate-200 shadow-sm">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" wire:model.live="updateHargaMaster" class="w-5 h-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 transition-all">
                        <span class="text-sm font-semibold text-slate-700">Update harga beli di Master Data sesuai dengan form ini</span>
                    </label>
                </div>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-sm text-slate-500">{{ count($cart) }} item</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xl font-bold text-slate-800">Total: Rp {{ number_format($this->total, 0, ',', '.') }}</p>
                    </div>
                </div>

                <button @click="showConfirm = true" wire:loading.attr="disabled" wire:target="processRestock" class="w-full py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-semibold shadow-lg shadow-indigo-500/30 hover:shadow-indigo-500/50 transition-all text-lg flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg wire:loading.remove wire:target="processRestock" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    <svg wire:loading wire:target="processRestock" class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                    <span wire:loading.remove wire:target="processRestock">Simpan & Masukkan Stok</span>
                    <span wire:loading wire:target="processRestock">Menyimpan...</span>
                </button>

                {{-- Confirm Modal --}}
                <div x-show="showConfirm" x-cloak x-transition.opacity class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-end sm:items-center justify-center p-4" @keydown.escape.window="showConfirm = false">
                    <div @click="showConfirm = false" class="absolute inset-0"></div>
                    <div x-show="showConfirm" x-transition.scale.origin.bottom class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
                        <div class="mx-auto w-14 h-14 rounded-full bg-indigo-100 flex items-center justify-center mb-4">
                            <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-800 mb-2">Konfirmasi Pembelian</h3>
                        <p class="text-sm text-slate-500 mb-6">Apakah data pembelian sudah benar? Stok produk akan otomatis bertambah setelah disimpan.</p>
                        <div class="flex gap-3">
                            <button type="button" @click="showConfirm = false" class="flex-1 py-2.5 rounded-xl bg-slate-100 text-slate-700 text-sm font-semibold hover:bg-slate-200 transition-colors">Batal</button>
                            <button type="button" wire:click="processRestock" @click="showConfirm = false" class="flex-1 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-lg transition-colors">Ya, Simpan</button>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
