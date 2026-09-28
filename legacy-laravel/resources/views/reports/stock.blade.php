<x-app-layout>
@section('title', 'Laporan Mutasi Stok')
<x-slot name="header">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-slate-800">Laporan Mutasi Stok</h2>
    </div>
</x-slot>

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 mb-6">
    <form method="GET" class="flex flex-col sm:flex-row flex-wrap gap-3 sm:items-end">
        <div class="w-full sm:flex-1 sm:min-w-[250px]">
            <label class="text-xs font-medium text-slate-500">Pilih Produk</label>
            <select name="product_id" class="w-full mt-1 rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-200">
                <option value="">-- Semua Produk --</option>
                @foreach($products as $p)
                <option value="{{ $p->id }}" {{ $productId == $p->id ? 'selected' : '' }}>{{ $p->sku }} - {{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-full sm:w-auto">
            <label class="text-xs font-medium text-slate-500">Dari</label>
            <input type="date" name="start_date" value="{{ $startDate }}" class="w-full mt-1 rounded-xl border-slate-200 text-sm">
        </div>
        <div class="w-full sm:w-auto">
            <label class="text-xs font-medium text-slate-500">Sampai</label>
            <input type="date" name="end_date" value="{{ $endDate }}" class="w-full mt-1 rounded-xl border-slate-200 text-sm">
        </div>
        <button type="submit" class="w-full sm:w-auto px-4 py-2 mt-2 sm:mt-0 bg-indigo-600 text-white rounded-xl text-sm font-medium">Tampilkan</button>
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <!-- Mobile: card list -->
    <div class="md:hidden divide-y divide-slate-100">
        @forelse($movements as $mv)
        <div class="p-4">
            <div class="flex items-start justify-between gap-3 mb-2">
                <div class="min-w-0">
                    <p class="font-medium text-slate-700 truncate">{{ $mv->product->name }}</p>
                    <p class="font-mono text-xs text-slate-400 mt-0.5">{{ $mv->product->sku }} · {{ $mv->created_at->format('d/m/Y H:i') }}</p>
                </div>
                <span class="shrink-0 px-2 py-0.5 rounded-full text-xs font-semibold
                    {{ $mv->type === 'in' || $mv->type === 'return' ? 'bg-emerald-100 text-emerald-700' :
                       ($mv->type === 'out' ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-700') }}">
                    {{ $mv->type_label }}
                </span>
            </div>
            <div class="flex items-center gap-6 text-sm">
                <span class="font-bold {{ $mv->type === 'in' || $mv->type === 'return' ? 'text-emerald-600' : 'text-red-600' }}">{{ $mv->type === 'in' || $mv->type === 'return' ? '+' : '-' }}{{ $mv->qty }}</span>
                <span class="text-slate-800">Stok akhir: <span class="font-bold">{{ $mv->stok_sesudah }}</span></span>
                <span class="ml-auto text-xs text-slate-400">{{ $mv->user->name }}</span>
            </div>
            @if($mv->keterangan)
            <p class="mt-2 text-xs text-slate-500">{{ $mv->keterangan }}</p>
            @endif
        </div>
        @empty
        <div class="py-8 text-center text-slate-400">Belum ada data mutasi stok.</div>
        @endforelse
    </div>

    <!-- Desktop: table -->
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="text-left py-3 px-4 font-semibold text-slate-600">Waktu</th>
                    <th class="text-left py-3 px-4 font-semibold text-slate-600">Produk</th>
                    <th class="text-center py-3 px-4 font-semibold text-slate-600">Tipe</th>
                    <th class="text-center py-3 px-4 font-semibold text-slate-600">Qty</th>
                    <th class="text-center py-3 px-4 font-semibold text-slate-600">Stok Akhir</th>
                    <th class="text-left py-3 px-4 font-semibold text-slate-600">Keterangan</th>
                    <th class="text-left py-3 px-4 font-semibold text-slate-600">Oleh</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movements as $mv)
                <tr class="border-b border-slate-50 hover:bg-slate-50/50">
                    <td class="py-3 px-4 text-slate-600">{{ $mv->created_at->format('d/m/Y H:i') }}</td>
                    <td class="py-3 px-4">
                        <span class="font-medium text-slate-700 block">{{ $mv->product->name }}</span>
                        <span class="text-xs text-slate-400 font-mono">{{ $mv->product->sku }}</span>
                    </td>
                    <td class="py-3 px-4 text-center">
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                            {{ $mv->type === 'in' || $mv->type === 'return' ? 'bg-emerald-100 text-emerald-700' :
                               ($mv->type === 'out' ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-700') }}">
                            {{ $mv->type_label }}
                        </span>
                    </td>
                    <td class="py-3 px-4 text-center font-bold {{ $mv->type === 'in' || $mv->type === 'return' ? 'text-emerald-600' : 'text-red-600' }}">
                        {{ $mv->type === 'in' || $mv->type === 'return' ? '+' : '-' }}{{ $mv->qty }}
                    </td>
                    <td class="py-3 px-4 text-center font-bold text-slate-800">{{ $mv->stok_sesudah }}</td>
                    <td class="py-3 px-4 text-slate-500 text-xs">{{ $mv->keterangan }}</td>
                    <td class="py-3 px-4 text-slate-600 text-xs">{{ $mv->user->name }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-8 text-center text-slate-400">Belum ada data mutasi stok.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 border-t border-slate-100">{{ $movements->appends(request()->query())->links() }}</div>
</div>
</x-app-layout>
