<x-app-layout>
@section('title', 'Peringatan Stok Menipis')
<x-slot name="header">
    <h2 class="text-2xl font-bold text-slate-800">Peringatan Stok Menipis</h2>
</x-slot>

<div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 mb-6 flex gap-4 items-center">
    <div class="w-12 h-12 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center shrink-0">
        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
    </div>
    <div>
        <h3 class="font-bold text-amber-900 text-lg">Perhatian!</h3>
        <p class="text-amber-800 text-sm mt-1">Produk-produk di bawah ini memiliki stok sama dengan atau kurang dari batas minimum stok (Re-order point). Segera lakukan pemesanan ke supplier.</p>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <!-- Mobile: card list -->
    <div class="md:hidden divide-y divide-slate-100">
        @forelse($products as $product)
        <div class="p-4">
            <div class="flex items-start justify-between gap-3 mb-2">
                <div class="min-w-0">
                    <p class="font-medium text-slate-700 truncate">{{ $product->name }}</p>
                    <p class="font-mono text-xs text-slate-400 mt-0.5">{{ $product->sku }} · {{ $product->category->name }}</p>
                </div>
                <span class="shrink-0 px-3 py-1 rounded-full text-sm font-bold {{ $product->stok <= 0 ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }}">{{ $product->stok }} {{ $product->satuan }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-xs text-slate-500">Min. stok: {{ $product->min_stok }} {{ $product->satuan }}</span>
                <a href="{{ route('purchases.create') }}" class="inline-flex px-3 py-1.5 bg-indigo-600 text-white rounded-lg text-xs font-semibold hover:bg-indigo-700 transition-colors">Catat Pembelian</a>
            </div>
        </div>
        @empty
        <div class="py-12 text-center text-emerald-600 font-medium">
            <svg class="w-12 h-12 mx-auto mb-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Semua stok produk dalam kondisi aman.
        </div>
        @endforelse
    </div>

    <!-- Desktop: table -->
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="text-left py-3 px-4 font-semibold text-slate-600">SKU</th>
                    <th class="text-left py-3 px-4 font-semibold text-slate-600">Produk</th>
                    <th class="text-left py-3 px-4 font-semibold text-slate-600">Kategori</th>
                    <th class="text-center py-3 px-4 font-semibold text-slate-600">Stok Saat Ini</th>
                    <th class="text-center py-3 px-4 font-semibold text-slate-600">Min. Stok</th>
                    <th class="text-center py-3 px-4 font-semibold text-slate-600">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                <tr class="border-b border-slate-50 hover:bg-slate-50/50">
                    <td class="py-3 px-4 font-mono text-xs text-slate-500">{{ $product->sku }}</td>
                    <td class="py-3 px-4 font-medium text-slate-700">{{ $product->name }}</td>
                    <td class="py-3 px-4 text-slate-600">{{ $product->category->name }}</td>
                    <td class="py-3 px-4 text-center">
                        <span class="px-3 py-1 rounded-full text-sm font-bold {{ $product->stok <= 0 ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }}">
                            {{ $product->stok }} {{ $product->satuan }}
                        </span>
                    </td>
                    <td class="py-3 px-4 text-center font-medium text-slate-600">{{ $product->min_stok }} {{ $product->satuan }}</td>
                    <td class="py-3 px-4 text-center">
                        <a href="{{ route('purchases.create') }}" class="inline-flex px-3 py-1 bg-indigo-50 text-indigo-600 rounded-lg text-xs font-semibold hover:bg-indigo-100 transition-colors">
                            Catat Pembelian
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-12 text-center text-emerald-600 font-medium">
                        <svg class="w-12 h-12 mx-auto mb-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Semua stok produk dalam kondisi aman.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 border-t border-slate-100">{{ $products->links() }}</div>
</div>
</x-app-layout>
