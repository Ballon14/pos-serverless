<div>
    @if (session()->has('pos-success'))
        <div x-data="{ open: true }" x-init="setTimeout(() => open = false, 3000)" @keydown.escape.window="open = false" x-show="open" x-cloak
            class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/50">
            <div class="w-full max-w-sm bg-white rounded-3xl shadow-2xl shadow-slate-900/30 overflow-hidden">
                <div class="p-6 text-center">
                    <div
                        class="mx-auto w-14 h-14 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-4">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 mb-1">Transaksi Berhasil</h3>
                    <p class="text-sm text-slate-500 leading-relaxed">{{ session('pos-success') }}</p>
                    @if ($lastSaleId)
                        <a href="{{ route('sales.receipt', $lastSaleId) }}"
                            class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-700 hover:text-emerald-900 underline">Cetak
                            Struk →</a>
                    @endif
                </div>
                <div class="px-6 pb-6">
                    <button type="button" @click="open = false"
                        class="w-full py-2.5 rounded-xl font-semibold text-white shadow-lg bg-emerald-600 shadow-emerald-500/30 transition-all hover:brightness-110 active:scale-[0.98]">OK</button>
                </div>
            </div>
        </div>
    @endif

    @if (session()->has('pos-error'))
        <div class="mb-4 rounded-xl bg-red-50 border border-red-200 p-4">
            <span class="text-sm font-medium text-red-800">{{ session('pos-error') }}</span>
        </div>
    @endif

    <!-- ===================== POS (Livewire) ===================== -->

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:h-[calc(100vh-12rem)]">
        <!-- Left: Product Search -->
        <div class="lg:col-span-1 bg-white rounded-2xl shadow-sm border border-slate-100 flex flex-col overflow-hidden">
            <div class="p-4 border-b border-slate-100">
                <h3 class="text-lg font-semibold text-slate-800 mb-3">Cari Produk</h3>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input wire:model.live.debounce.300ms="search" wire:keydown.enter="addBySearchEnter" type="text"
                        placeholder="Nama produk, kategori, atau SKU..."
                        class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 text-sm transition-all">
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-4 space-y-2 max-h-[45vh] lg:max-h-none">
                @forelse($products as $product)
                    <button wire:click="addToCart({{ $product->id }})"
                        class="w-full text-left p-3 rounded-xl border border-slate-100 hover:border-indigo-300 hover:bg-indigo-50/50 transition-all group">
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-700 group-hover:text-indigo-700 truncate">
                                    {{ $product->name }}</p>
                                <p class="text-xs text-slate-400 mt-0.5">{{ $product->sku }} · Stok:
                                    {{ $product->stok }} {{ $product->satuan }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-sm font-bold text-indigo-600">Rp
                                    {{ number_format($product->harga_jual, 0, ',', '.') }}</p>
                                <svg class="w-5 h-5 text-slate-300 group-hover:text-indigo-500 ml-auto mt-1 transition-colors"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                            </div>
                        </div>
                    </button>
                @empty
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 text-slate-300 mx-auto mb-2" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-sm text-slate-400">Produk tidak ditemukan</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Right: Cart -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-100 flex flex-col overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">
                    Keranjang
                    @if (count($cart) > 0)
                        <span
                            class="ml-2 bg-indigo-100 text-indigo-700 text-xs font-bold px-2 py-0.5 rounded-full">{{ count($cart) }}
                            item</span>
                    @endif
                </h3>
                @if (count($cart) > 0)
                    <button wire:click="clearCart" class="text-sm text-red-500 hover:text-red-700 font-medium">Hapus
                        Semua</button>
                @endif
            </div>

            <!-- Mobile cart cards -->
            <div class="lg:hidden flex-1 overflow-y-auto max-h-[50vh] lg:max-h-none">
                @if (count($cart) > 0)
                    <div class="divide-y divide-slate-100">
                        @foreach ($cart as $key => $item)
                            <div class="p-4 space-y-3" wire:key="{{ $key }}">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="font-medium text-slate-700 truncate">{{ $item['name'] }}
                                            @if(!empty($item['is_grosir']) && isset($item['active_tier']))
                                                <span class="inline-flex items-center ml-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-700 uppercase">Grosir ≥{{ $item['active_tier'] }}</span>
                                            @elseif(!empty($item['is_grosir']))
                                                <span class="inline-flex items-center ml-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-700 uppercase">Grosir</span>
                                            @endif
                                        </p>
                                        <p class="text-xs text-slate-400 mt-0.5">
                                            @if(!empty($item['is_grosir']))
                                                <span class="line-through text-slate-300">Rp {{ number_format($item['harga_jual_asli'] ?? $item['harga'], 0, ',', '.') }}</span>
                                                <span class="text-amber-600 font-semibold">Rp {{ number_format($item['harga'], 0, ',', '.') }}</span> / unit
                                            @else
                                                Rp {{ number_format($item['harga'], 0, ',', '.') }} / unit
                                            @endif
                                        </p>
                                    </div>
                                    <button wire:click="removeItem('{{ $key }}')"
                                        class="p-1.5 rounded-lg text-red-400 hover:bg-red-50 hover:text-red-600 transition-colors shrink-0">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-2">
                                        <button
                                            wire:click="updateQty('{{ $key }}', {{ max(0, $item['qty'] - 1) }})"
                                            class="w-8 h-8 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors text-lg leading-none">−</button>
                                        <input type="number"
                                            wire:change="updateQty('{{ $key }}', $event.target.value)"
                                            value="{{ $item['qty'] }}" min="1" max="{{ $item['stok'] }}"
                                            class="w-14 text-center rounded-lg border border-slate-200 py-1 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-200">
                                        <button wire:click="updateQty('{{ $key }}', {{ $item['qty'] + 1 }})"
                                            class="w-8 h-8 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors text-lg leading-none">+</button>
                                    </div>
                                    <p class="font-semibold text-slate-800 text-sm">Rp
                                        {{ number_format($item['subtotal'], 0, ',', '.') }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center h-full text-center py-12">
                        <svg class="w-16 h-16 text-slate-200 mb-3" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
                        </svg>
                        <p class="text-slate-400 font-medium">Keranjang kosong</p>
                        <p class="text-slate-300 text-sm mt-1">Cari dan tambahkan produk</p>
                    </div>
                @endif
            </div>

            <!-- Desktop cart table -->
            <div class="hidden lg:block flex-1 overflow-y-auto">
                @if (count($cart) > 0)
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 sticky top-0">
                            <tr>
                                <th class="text-left py-3 px-4 font-medium text-slate-500">Produk</th>
                                <th class="text-center py-3 px-4 font-medium text-slate-500 w-20">Qty</th>
                                <th class="text-right py-3 px-4 font-medium text-slate-500">Harga</th>
                                <th class="text-right py-3 px-4 font-medium text-slate-500">Subtotal</th>
                                <th class="w-10"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($cart as $key => $item)
                                <tr class="border-b border-slate-50 hover:bg-slate-50/50"
                                    wire:key="{{ $key }}">
                                    <td class="py-3 px-4">
                                        <p class="font-medium text-slate-700">{{ $item['name'] }}
                                            @if(!empty($item['is_grosir']) && isset($item['active_tier']))
                                                <span class="inline-flex items-center ml-1.5 px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-700 uppercase">Grosir ≥{{ $item['active_tier'] }}</span>
                                            @elseif(!empty($item['is_grosir']))
                                                <span class="inline-flex items-center ml-1.5 px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-700 uppercase">Grosir</span>
                                            @endif
                                        </p>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <input type="number"
                                            wire:change="updateQty('{{ $key }}', $event.target.value)"
                                            value="{{ $item['qty'] }}" min="1" max="{{ $item['stok'] }}"
                                            class="w-16 text-center rounded-lg border border-slate-200 py-1 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-200">
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        @if(!empty($item['is_grosir']))
                                            <span class="text-xs line-through text-slate-300 block">Rp {{ number_format($item['harga_jual_asli'] ?? $item['harga'], 0, ',', '.') }}</span>
                                            <span class="text-amber-700 font-semibold">Rp {{ number_format($item['harga'], 0, ',', '.') }}</span>
                                        @else
                                            <span class="text-slate-600">Rp {{ number_format($item['harga'], 0, ',', '.') }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-right font-semibold text-slate-800">Rp
                                        {{ number_format($item['subtotal'], 0, ',', '.') }}</td>
                                    <td class="py-3 px-2">
                                        <button wire:click="removeItem('{{ $key }}')"
                                            class="p-1 rounded-lg text-red-400 hover:bg-red-50 hover:text-red-600 transition-colors">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="flex flex-col items-center justify-center h-full text-center py-12">
                        <svg class="w-16 h-16 text-slate-200 mb-3" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
                        </svg>
                        <p class="text-slate-400 font-medium">Keranjang kosong</p>
                        <p class="text-slate-300 text-sm mt-1">Cari dan tambahkan produk</p>
                    </div>
                @endif
            </div>

            <!-- Footer: Total & Checkout -->
            @if (count($cart) > 0)
                <div class="border-t border-slate-200 p-4 bg-slate-50/50">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3">
                        <div>
                            <label class="text-xs text-slate-500">Diskon Total</label>
                            <div class="flex items-center gap-2">
                                <div class="relative">
                                    <input type="number" wire:model.live="diskon" min="0"
                                        class="w-32 rounded-lg border border-slate-200 py-1.5 pl-6 pr-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-200">
                                    <span
                                        class="absolute left-2 top-1/2 -translate-y-1/2 text-xs text-slate-400">Rp</span>
                                </div>
                                <div class="relative">
                                    <input type="number" wire:model.live="diskonPersen" min="0"
                                        max="100"
                                        class="w-20 rounded-lg border border-slate-200 py-1.5 pl-6 pr-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-200">
                                    <span
                                        class="absolute left-2 top-1/2 -translate-y-1/2 text-xs text-slate-400">%</span>
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-slate-500">Subtotal: Rp
                                {{ number_format($this->subtotal, 0, ',', '.') }}</p>
                            <p class="text-xl font-bold text-slate-800">Total: Rp
                                {{ number_format($this->grandTotal, 0, ',', '.') }}</p>
                        </div>
                    </div>

                    <div class="mb-4 pt-3 border-t border-slate-200">
                        <label class="text-xs font-semibold text-slate-700 block mb-1">Metode Pembayaran</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" wire:click="setPaymentMethod('cash')"
                                class="flex items-center justify-center gap-2 py-2.5 rounded-xl border text-sm font-semibold transition-all {{ $this->paymentMethod === 'cash' ? 'bg-indigo-600 border-indigo-600 text-white shadow-lg shadow-indigo-500/30' : 'bg-white border-slate-200 text-slate-600 hover:border-indigo-300' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Tunai
                            </button>
                            <button type="button" wire:click="setPaymentMethod('qris')"
                                class="flex items-center justify-center gap-2 py-2.5 rounded-xl border text-sm font-semibold transition-all {{ $this->paymentMethod === 'qris' ? 'bg-indigo-600 border-indigo-600 text-white shadow-lg shadow-indigo-500/30' : 'bg-white border-slate-200 text-slate-600 hover:border-indigo-300' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 10m-1 0a1 1 0 112 0 1 1 0 01-2 0zm12 0a1 1 0 112 0 1 1 0 01-2 0zm-12 6a1 1 0 112 0 1 1 0 01-2 0zm12 0a1 1 0 112 0 1 1 0 01-2 0z" />
                                </svg>
                                QRIS
                            </button>
                        </div>
                    </div>

                    @if ($this->paymentMethod === 'qris')
                        <div class="mb-4 rounded-xl border border-indigo-200 bg-indigo-50/60 p-4">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 10m-1 0a1 1 0 112 0 1 1 0 01-2 0zm12 0a1 1 0 112 0 1 1 0 01-2 0zm-12 6a1 1 0 112 0 1 1 0 01-2 0zm12 0a1 1 0 112 0 1 1 0 01-2 0z" />
                                </svg>
                                <p class="text-sm font-semibold text-indigo-800">Pembayaran QRIS</p>
                            </div>
                            <p class="text-xs text-indigo-600 mb-3">Minta pelanggan membayar ke kode QRIS di bawah
                                melalui aplikasi m-banking, lalu konfirmasi setelah pembayaran diterima.</p>
                            <div class="bg-white rounded-xl border border-indigo-100 p-4 text-center">
                                @if ($this->qrisCode)
                                    <p class="font-mono text-sm font-bold text-slate-800 break-all select-all">
                                        {{ $this->qrisCode }}</p>
                                    <p class="text-xs text-slate-500 mt-2">{{ config('stockku.qris_holder') }}</p>
                                @else
                                    <p class="text-sm text-amber-600">Kode QRIS belum diatur. Tambahkan <code
                                            class="font-mono text-xs">QRIS_STATIC_CODE</code> di file <code
                                            class="font-mono text-xs">.env</code>.</p>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="text-xs font-semibold text-slate-700 block mb-1">Uang dari Pelanggan
                                (Rp)</label>
                            <input type="number" wire:model.live="bayar" min="0"
                                class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-base sm:text-lg font-bold text-slate-800 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200"
                                placeholder="0">

                            <!-- Shortcut Nominal -->
                            <div class="flex flex-wrap gap-2 mt-2">
                                <button type="button" wire:click="$set('bayar', {{ $this->grandTotal }})"
                                    class="px-2.5 py-1 bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-lg text-xs font-bold hover:bg-emerald-200 transition-colors shadow-sm">Uang
                                    Pas</button>
                                <button type="button" wire:click="$set('bayar', 10000)"
                                    class="px-2.5 py-1 bg-slate-50 text-slate-600 border border-slate-200 rounded-lg text-xs font-semibold hover:bg-slate-100 transition-colors shadow-sm">10k</button>
                                <button type="button" wire:click="$set('bayar', 20000)"
                                    class="px-2.5 py-1 bg-slate-50 text-slate-600 border border-slate-200 rounded-lg text-xs font-semibold hover:bg-slate-100 transition-colors shadow-sm">20k</button>
                                <button type="button" wire:click="$set('bayar', 50000)"
                                    class="px-2.5 py-1 bg-slate-50 text-slate-600 border border-slate-200 rounded-lg text-xs font-semibold hover:bg-slate-100 transition-colors shadow-sm">50k</button>
                                <button type="button" wire:click="$set('bayar', 100000)"
                                    class="px-2.5 py-1 bg-slate-50 text-slate-600 border border-slate-200 rounded-lg text-xs font-semibold hover:bg-slate-100 transition-colors shadow-sm">100k</button>
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-700 block mb-1">Kembalian (Rp)</label>
                            <div
                                class="w-full rounded-xl border border-slate-200 py-2.5 px-3 text-base sm:text-lg font-bold bg-white {{ $this->kembalian > 0 ? 'text-emerald-600' : 'text-slate-800' }}">
                                {{ number_format($this->kembalian, 0, ',', '.') }}
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <input type="text" wire:model="catatan"
                            class="w-full rounded-xl border border-slate-200 py-2 px-3 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-200"
                            placeholder="Catatan transaksi (opsional)...">
                    </div>

                    @if ($this->bayar < $this->grandTotal && $this->bayar > 0)
                        <p class="mb-3 text-sm font-medium text-red-600 flex items-center gap-1.5">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                            Uang pelanggan kurang Rp {{ number_format($this->grandTotal - $this->bayar, 0, ',', '.') }}
                            dari total belanja.
                        </p>
                    @endif

                    <div x-data="{ showConfirm: false }">
                        <button @click="showConfirm = true" wire:loading.attr="disabled" wire:target="processPayment"
                            class="w-full py-3 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-xl font-semibold shadow-lg shadow-emerald-500/30 hover:shadow-emerald-500/50 transition-all text-lg flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                            {{ $this->bayar < $this->grandTotal ? 'disabled' : '' }}>
                            <svg wire:loading.remove wire:target="processPayment" class="w-6 h-6" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <svg wire:loading wire:target="processPayment" class="w-6 h-6 animate-spin"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z">
                                </path>
                            </svg>
                            <span wire:loading.remove wire:target="processPayment">Proses Transaksi</span>
                            <span wire:loading wire:target="processPayment">Memproses...</span>
                        </button>

                        <div x-show="showConfirm" x-cloak x-transition.opacity
                            class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-end sm:items-center justify-center p-4"
                            @keydown.escape.window="showConfirm = false">
                            <div @click="showConfirm = false" class="absolute inset-0"></div>
                            <div x-show="showConfirm" x-transition.scale.origin.bottom
                                class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
                                <div
                                    class="mx-auto w-14 h-14 rounded-full bg-emerald-100 flex items-center justify-center mb-4">
                                    <svg class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-bold text-slate-800 mb-2">Proses Transaksi</h3>
                                <p class="text-sm text-slate-500 mb-6">Apakah Anda yakin uang pelanggan sudah sesuai
                                    dan ingin memproses transaksi ini?</p>
                                <div class="flex gap-3">
                                    <button type="button" @click="showConfirm = false"
                                        class="flex-1 py-2.5 rounded-xl bg-slate-100 text-slate-700 text-sm font-semibold hover:bg-slate-200 transition-colors">Batal</button>
                                    <button type="button" wire:click="processPayment" @click="showConfirm = false"
                                        class="flex-1 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-lg transition-colors">Ya,
                                        Proses</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
