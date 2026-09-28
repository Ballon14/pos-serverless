<x-app-layout>
@section('title', 'Kartu Stok')
<x-slot name="header">
    <h2 class="text-2xl font-bold text-slate-800">Kartu Stok</h2>
</x-slot>

<!-- Filter -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[250px]">
            <label class="text-xs font-medium text-slate-500">Pilih Produk</label>
            <select name="product_id" class="w-full mt-1 rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-200">
                <option value="">-- Pilih Produk --</option>
                @foreach($products as $p)
                <option value="{{ $p->id }}" {{ $productId == $p->id ? 'selected' : '' }}>{{ $p->sku }} - {{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-medium text-slate-500">Dari</label>
            <input type="date" name="start_date" value="{{ $startDate }}" class="w-full mt-1 rounded-xl border-slate-200 text-sm">
        </div>
        <div>
            <label class="text-xs font-medium text-slate-500">Sampai</label>
            <input type="date" name="end_date" value="{{ $endDate }}" class="w-full mt-1 rounded-xl border-slate-200 text-sm">
        </div>
        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-xl text-sm font-medium">Tampilkan</button>
    </form>
</div>

@if($product)
<div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-6 mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div class="min-w-0">
        <h3 class="text-xl font-bold text-indigo-900 truncate">{{ $product->name }}</h3>
        <p class="text-sm text-indigo-700 mt-1">SKU: {{ $product->sku }} | Kategori: {{ $product->category->name }}</p>
    </div>
    <div class="text-left sm:text-right shrink-0">
        <p class="text-sm font-medium text-indigo-700">Stok Saat Ini</p>
        <p class="text-4xl font-black text-indigo-600">{{ $product->stok }} <span class="text-lg font-bold text-indigo-400">{{ $product->satuan }}</span></p>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 mb-6">
    <h4 class="text-sm font-semibold text-slate-700 mb-3">Koreksi Stok (Penyesuaian)</h4>
    <form method="POST" action="{{ route('stock.adjust') }}" class="flex flex-wrap gap-3 items-end">
        @csrf
        <input type="hidden" name="product_id" value="{{ $product->id }}">
        <div>
            <label class="text-xs font-medium text-slate-500">Jumlah (+/-)</label>
            <input type="number" name="qty" min="-999999" max="999999" required placeholder="mis. 5 atau -3"
                class="w-40 mt-1 rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-200">
        </div>
        <div class="flex-1 min-w-[250px]">
            <label class="text-xs font-medium text-slate-500">Keterangan</label>
            <input type="text" name="keterangan" maxlength="500" placeholder="mis. Opname fisik, stok hilang, bonus dari supplier"
                class="w-full mt-1 rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-200">
        </div>
        <button type="submit" class="px-4 py-2 bg-amber-500 text-white rounded-xl text-sm font-medium">Terapkan Koreksi</button>
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <!-- Mobile: card list -->
    <div class="md:hidden divide-y divide-slate-100">
        @forelse($movements as $mv)
        <div class="p-4">
            <div class="flex items-start justify-between gap-3 mb-2">
                <div class="min-w-0">
                    <p class="text-sm text-slate-600">{{ $mv->created_at->format('d/m/Y H:i') }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">{{ $mv->user->name }}</p>
                </div>
                <span class="shrink-0 px-2 py-0.5 rounded-full text-xs font-semibold
                    {{ $mv->type === 'in' || $mv->type === 'return' ? 'bg-emerald-100 text-emerald-700' :
                       ($mv->type === 'out' ? 'bg-red-100 text-red-700' :
                        ($mv->type === 'adjustment' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700')) }}">
                    {{ $mv->type_label }}
                </span>
            </div>
            <div class="flex items-center gap-6 text-sm">
                <span class="font-semibold text-emerald-600">{{ ($mv->type === 'in' || $mv->type === 'return' || ($mv->type === 'adjustment' && $mv->qty > 0)) ? '+' . $mv->qty : '-' }}</span>
                <span class="font-semibold text-red-600">{{ $mv->type === 'out' ? '-' . $mv->qty : ($mv->type === 'adjustment' && $mv->qty < 0 ? $mv->qty : '-') }}</span>
                <span class="ml-auto text-slate-800">Sisa: <span class="font-bold">{{ $mv->stok_sesudah }}</span></span>
            </div>
            @if($mv->keterangan)
            <p class="mt-2 text-xs text-slate-500">{{ $mv->keterangan }}</p>
            @endif
        </div>
        @empty
        <div class="py-8 text-center text-slate-400">Belum ada pergerakan stok untuk produk ini pada periode tersebut.</div>
        @endforelse
    </div>

    <!-- Desktop: table -->
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="text-left py-3 px-4 font-semibold text-slate-600">Waktu</th>
                    <th class="text-center py-3 px-4 font-semibold text-slate-600">Tipe</th>
                    <th class="text-center py-3 px-4 font-semibold text-slate-600">Masuk</th>
                    <th class="text-center py-3 px-4 font-semibold text-slate-600">Keluar</th>
                    <th class="text-center py-3 px-4 font-semibold text-slate-600">Sisa Stok</th>
                    <th class="text-left py-3 px-4 font-semibold text-slate-600">Keterangan</th>
                    <th class="text-left py-3 px-4 font-semibold text-slate-600">Oleh</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movements as $mv)
                <tr class="border-b border-slate-50 hover:bg-slate-50/50">
                    <td class="py-3 px-4 text-slate-600">{{ $mv->created_at->format('d/m/Y H:i') }}</td>
                    <td class="py-3 px-4 text-center">
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                            {{ $mv->type === 'in' || $mv->type === 'return' ? 'bg-emerald-100 text-emerald-700' :
                               ($mv->type === 'out' ? 'bg-red-100 text-red-700' :
                                ($mv->type === 'adjustment' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700')) }}">
                            {{ $mv->type_label }}
                        </span>
                    </td>
                    <td class="py-3 px-4 text-center font-semibold text-emerald-600">{{ ($mv->type === 'in' || $mv->type === 'return' || ($mv->type === 'adjustment' && $mv->qty > 0)) ? '+' . $mv->qty : '-' }}</td>
                    <td class="py-3 px-4 text-center font-semibold text-red-600">{{ $mv->type === 'out' ? '-' . $mv->qty : ($mv->type === 'adjustment' && $mv->qty < 0 ? $mv->qty : '-') }}</td>
                    <td class="py-3 px-4 text-center font-bold text-slate-800">{{ $mv->stok_sesudah }}</td>
                    <td class="py-3 px-4 text-slate-500 text-xs">{{ $mv->keterangan }}</td>
                    <td class="py-3 px-4 text-slate-600 text-xs">{{ $mv->user->name }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-8 text-center text-slate-400">Belum ada pergerakan stok untuk produk ini pada periode tersebut.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 border-t border-slate-100">{{ $movements->appends(request()->query())->links() }}</div>
</div>
@else
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-12 text-center">
    <svg class="w-16 h-16 text-slate-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
    <h3 class="text-lg font-medium text-slate-800">Silakan Pilih Produk</h3>
    <p class="text-sm text-slate-500 mt-1">Pilih produk di filter atas untuk melihat riwayat kartu stoknya.</p>
</div>
@endif

</x-app-layout>
