<x-app-layout>
@section('title', 'Detail Pembelian')
<x-slot name="header">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-slate-800">Detail Pembelian</h2>
        <a href="{{ route('purchases.index') }}" class="hidden sm:inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-xl text-sm font-semibold hover:bg-slate-50 hover:text-slate-900 transition-all shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali
        </a>
    </div>
</x-slot>

<div class="max-w-5xl">
    {{-- Header Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden mb-6">
        {{-- Invoice badge header --}}
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-5">
            <div class="flex flex-wrap gap-4 items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <div>
                        <p class="text-indigo-100 text-xs font-semibold uppercase tracking-wide">Invoice Pembelian</p>
                        <p class="text-white font-bold font-mono text-xl mt-0.5">{{ $purchase->invoice_number }}</p>
                    </div>
                </div>
                <span class="px-4 py-1.5 rounded-full text-sm font-bold bg-emerald-400/20 text-emerald-100 border border-emerald-400/30 backdrop-blur-sm">
                    ✓ Diterima
                </span>
            </div>
        </div>

        {{-- Info grid --}}
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6">
                <div class="flex items-start gap-4 p-4 rounded-xl bg-slate-50 border border-slate-100 hover:border-indigo-100 transition-colors">
                    <div class="w-10 h-10 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-slate-500 font-medium">Tanggal</p>
                        <p class="text-sm font-semibold text-slate-800 mt-0.5">{{ \Carbon\Carbon::parse($purchase->tanggal)->translatedFormat('d F Y') }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-4 p-4 rounded-xl bg-slate-50 border border-slate-100 hover:border-purple-100 transition-colors">
                    <div class="w-10 h-10 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-slate-500 font-medium">Supplier</p>
                        <p class="text-sm font-semibold text-slate-800 mt-0.5 truncate">{{ $purchase->supplier->name }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-4 p-4 rounded-xl bg-slate-50 border border-slate-100 hover:border-teal-100 transition-colors">
                    <div class="w-10 h-10 rounded-lg bg-teal-100 text-teal-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-slate-500 font-medium">Dicatat Oleh</p>
                        <p class="text-sm font-semibold text-slate-800 mt-0.5 truncate">{{ $purchase->user->name }}</p>
                    </div>
                </div>
            </div>

            @if($purchase->keterangan)
            <div class="mb-6 flex items-start gap-4 p-4 rounded-xl bg-slate-50 border border-slate-100">
                <div class="w-10 h-10 rounded-lg bg-slate-200/50 text-slate-600 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                </div>
                <div class="min-w-0 pt-0.5">
                    <p class="text-xs font-medium text-slate-500 mb-0.5">Keterangan / Referensi</p>
                    <p class="text-sm font-medium text-slate-700">{{ $purchase->keterangan }}</p>
                </div>
            </div>
            @endif

            @if($purchase->foto_nota)
            <div class="mb-2">
                <p class="text-xs font-semibold text-slate-600 mb-2 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Foto Nota / Bukti Pembelian
                </p>
                <a href="{{ asset('storage/' . $purchase->foto_nota) }}" target="_blank" class="inline-block rounded-xl overflow-hidden border-2 border-slate-200 hover:border-indigo-400 transition-all shadow-sm hover:shadow-md">
                    <img src="{{ asset('storage/' . $purchase->foto_nota) }}" alt="Foto Nota" class="w-48 h-auto object-cover">
                </a>
            </div>
            @endif
        </div>
    </div>

    {{-- Items Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                Item Produk
            </h3>
            <span class="text-xs font-bold text-slate-600 bg-slate-100 border border-slate-200 px-2.5 py-1 rounded-lg">{{ $purchase->items->count() }} item</span>
        </div>

        {{-- Mobile: card list --}}
        <div class="md:hidden divide-y divide-slate-100">
            @foreach($purchase->items as $item)
            <div class="p-4 bg-white">
                <div class="flex items-start justify-between gap-3 mb-2">
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-800 truncate">{{ $item->product ? $item->product->name : 'Produk Dihapus' }}</p>
                        @if($item->product)
                        <p class="text-xs text-slate-500 font-mono">{{ $item->product->sku }}</p>
                        @endif
                    </div>
                    <p class="text-sm font-bold text-indigo-600 shrink-0">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</p>
                </div>
                <div class="flex items-center gap-2 text-xs text-slate-600 bg-slate-50 px-2 py-1 rounded-lg w-fit">
                    <span class="font-medium text-slate-800">{{ $item->qty }}</span> <span>× Rp {{ number_format($item->harga, 0, ',', '.') }}</span>
                </div>
            </div>
            @endforeach
            <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                <span class="text-sm font-semibold text-slate-600">Total Pembelian</span>
                <span class="text-xl font-bold text-indigo-600">Rp {{ number_format($purchase->total, 0, ',', '.') }}</span>
            </div>
        </div>

        {{-- Desktop: table --}}
        <div class="hidden md:block">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="text-left py-3.5 px-6 font-semibold text-slate-600">Produk</th>
                        <th class="text-center py-3.5 px-4 font-semibold text-slate-600 w-24">Qty</th>
                        <th class="text-right py-3.5 px-6 font-semibold text-slate-600 w-48">Harga Beli Satuan</th>
                        <th class="text-right py-3.5 px-6 font-semibold text-slate-600 w-48">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($purchase->items as $item)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="py-4 px-6">
                            <p class="font-semibold text-slate-800">{{ $item->product ? $item->product->name : 'Produk Dihapus' }}</p>
                            @if($item->product)
                            <p class="text-xs text-slate-500 font-mono mt-0.5">{{ $item->product->sku }}</p>
                            @endif
                        </td>
                        <td class="py-4 px-4 text-center">
                            <span class="inline-flex items-center justify-center w-10 h-7 rounded-lg bg-indigo-50 text-indigo-700 font-bold text-sm border border-indigo-100">{{ $item->qty }}</span>
                        </td>
                        <td class="py-4 px-6 text-right font-medium text-slate-600">Rp {{ number_format($item->harga, 0, ',', '.') }}</td>
                        <td class="py-4 px-6 text-right font-bold text-slate-800">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-6 py-5 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                <span class="text-sm font-semibold text-slate-600 uppercase tracking-wide">Total Pembelian</span>
                <span class="text-2xl font-bold text-indigo-600">Rp {{ number_format($purchase->total, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <div class="sm:hidden pb-6">
        <a href="{{ route('purchases.index') }}" class="flex items-center justify-center gap-2 w-full px-4 py-3 bg-white border border-slate-200 text-slate-700 rounded-xl text-sm font-semibold shadow-sm">
            Kembali ke Daftar
        </a>
    </div>
</div>
</x-app-layout>
