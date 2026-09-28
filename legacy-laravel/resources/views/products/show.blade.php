<x-app-layout>
@section('title', 'Detail Produk')
<x-slot name="header"><h2 class="text-2xl font-bold text-slate-800">{{ $product->name }}</h2></x-slot>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
        @if($product->foto)
        <img src="{{ asset('storage/' . $product->foto) }}" class="w-full h-48 rounded-xl object-cover mb-4">
        @endif
        <div class="space-y-3 text-sm">
            <div class="flex justify-between"><span class="text-slate-500">SKU</span><span class="font-mono font-medium text-slate-700">{{ $product->sku }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">Kategori</span><span class="text-slate-700">{{ $product->category->name }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">Satuan</span><span class="text-slate-700">{{ $product->satuan }}</span></div>
            <hr class="border-slate-100">
            <div class="flex justify-between"><span class="text-slate-500">Harga Beli</span><span class="font-semibold text-slate-700">Rp {{ number_format($product->harga_beli, 0, ',', '.') }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">Harga Jual</span><span class="font-semibold text-indigo-600">Rp {{ number_format($product->harga_jual, 0, ',', '.') }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">Margin</span><span class="font-semibold text-emerald-600">Rp {{ number_format($product->harga_jual - $product->harga_beli, 0, ',', '.') }}</span></div>
            <hr class="border-slate-100">
            <div class="flex justify-between"><span class="text-slate-500">Stok</span><span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $product->isLowStock() ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">{{ $product->stok }} {{ $product->satuan }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">Min. Stok</span><span class="text-slate-700">{{ $product->min_stok }}</span></div>
        </div>
    </div>
    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
        <h3 class="text-lg font-semibold text-slate-800 mb-4">Riwayat Pergerakan Stok</h3>
        <!-- Mobile: card list -->
        <div class="md:hidden divide-y divide-slate-100">
            @forelse($stockMovements as $mv)
            <div class="py-3">
                <div class="flex items-start justify-between gap-3 mb-1.5">
                    <div>
                        <p class="text-xs text-slate-500">{{ $mv->created_at->format('d/m/Y H:i') }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">{{ $mv->keterangan }}</p>
                    </div>
                    <div class="text-right shrink-0">
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $mv->type === 'in' || $mv->type === 'return' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">{{ $mv->type_label }}</span>
                        <p class="mt-1 text-sm font-bold {{ $mv->type === 'in' || $mv->type === 'return' ? 'text-emerald-600' : 'text-red-600' }}">{{ $mv->type === 'in' || $mv->type === 'return' ? '+' : '-' }}{{ $mv->qty }}</p>
                    </div>
                </div>
                <p class="text-xs text-slate-500">Stok: <span class="font-semibold text-slate-700">{{ $mv->stok_sebelum }} → {{ $mv->stok_sesudah }}</span></p>
            </div>
            @empty
            <p class="py-6 text-center text-slate-400">Belum ada pergerakan stok</p>
            @endforelse
        </div>

        <!-- Desktop: table -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50"><tr>
                    <th class="text-left py-2 px-3 font-medium text-slate-500">Tanggal</th>
                    <th class="text-center py-2 px-3 font-medium text-slate-500">Tipe</th>
                    <th class="text-center py-2 px-3 font-medium text-slate-500">Qty</th>
                    <th class="text-center py-2 px-3 font-medium text-slate-500">Stok</th>
                    <th class="text-left py-2 px-3 font-medium text-slate-500">Keterangan</th>
                </tr></thead>
                <tbody>
                    @forelse($stockMovements as $mv)
                    <tr class="border-b border-slate-50">
                        <td class="py-2 px-3 text-slate-600">{{ $mv->created_at->format('d/m/Y H:i') }}</td>
                        <td class="py-2 px-3 text-center"><span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $mv->type === 'in' || $mv->type === 'return' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">{{ $mv->type_label }}</span></td>
                        <td class="py-2 px-3 text-center font-medium {{ $mv->type === 'in' || $mv->type === 'return' ? 'text-emerald-600' : 'text-red-600' }}">{{ $mv->type === 'in' || $mv->type === 'return' ? '+' : '-' }}{{ $mv->qty }}</td>
                        <td class="py-2 px-3 text-center text-slate-600">{{ $mv->stok_sebelum }} → {{ $mv->stok_sesudah }}</td>
                        <td class="py-2 px-3 text-slate-500 text-xs">{{ $mv->keterangan }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="py-6 text-center text-slate-400">Belum ada pergerakan stok</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $stockMovements->links() }}</div>
    </div>
</div>
</x-app-layout>
