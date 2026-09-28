<x-app-layout>
@section('title', 'Laporan Penjualan')
<x-slot name="header">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl sm:text-2xl font-bold text-slate-800">Laporan Penjualan</h2>
        <form method="GET" action="{{ route('reports.sales') }}" target="_blank" class="w-full sm:w-auto">
            <input type="hidden" name="start_date" value="{{ $startDate }}">
            <input type="hidden" name="end_date" value="{{ $endDate }}">
            <input type="hidden" name="user_id" value="{{ $userId }}">
            <input type="hidden" name="product_id" value="{{ $productId }}">
            <input type="hidden" name="export" value="pdf">
            <button type="submit" class="w-full sm:w-auto justify-center inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl text-sm font-semibold shadow-lg shadow-red-500/30 hover:shadow-red-500/50 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export PDF
            </button>
        </form>
    </div>
</x-slot>

<!-- Filter -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 mb-6">
    <form method="GET" class="flex flex-col sm:flex-row flex-wrap gap-3 sm:items-end">
        <div class="w-full sm:w-auto">
            <label class="text-xs font-medium text-slate-500">Dari</label>
            <input type="date" name="start_date" value="{{ $startDate }}" class="w-full mt-1 rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-200">
        </div>
        <div class="w-full sm:w-auto">
            <label class="text-xs font-medium text-slate-500">Sampai</label>
            <input type="date" name="end_date" value="{{ $endDate }}" class="w-full mt-1 rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-200">
        </div>
        @if(auth()->user()->hasRole(['admin']))
        <div class="w-full sm:w-48">
            <label class="text-xs font-medium text-slate-500">Kasir</label>
            <select name="user_id" class="w-full mt-1 rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-200">
                <option value="">Semua Kasir</option>
                @foreach($cashiers as $c)
                <option value="{{ $c->id }}" {{ $userId == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div class="w-full sm:flex-1 sm:min-w-[200px]">
            <label class="text-xs font-medium text-slate-500">Produk</label>
            <select name="product_id" class="w-full mt-1 rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-200">
                <option value="">Semua Produk</option>
                @foreach($products as $p)
                <option value="{{ $p->id }}" {{ $productId == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="w-full sm:w-auto px-4 py-2 mt-2 sm:mt-0 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700 transition-colors">Tampilkan</button>
    </form>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-6 overflow-hidden">
        <p class="text-sm font-medium text-indigo-700 mb-1">Total Transaksi Selesai</p>
        <p class="text-2xl sm:text-3xl font-bold text-indigo-900 truncate">{{ $data['summary']['total_transactions'] }}</p>
    </div>
    <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-6 overflow-hidden">
        <p class="text-sm font-medium text-emerald-700 mb-1">Total Pendapatan</p>
        <p class="text-2xl sm:text-3xl font-bold text-emerald-900 truncate" title="Rp {{ number_format($data['summary']['total_revenue'], 0, ',', '.') }}">Rp {{ number_format($data['summary']['total_revenue'], 0, ',', '.') }}</p>
    </div>
    <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-6 overflow-hidden">
        <p class="text-sm font-medium text-indigo-700 mb-1">Item Terjual</p>
        <p class="text-2xl sm:text-3xl font-bold text-indigo-900 truncate">{{ $data['summary']['total_items_sold'] }}</p>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="p-4 border-b border-slate-100 bg-slate-50">
        <h3 class="font-semibold text-slate-700">Rincian Penjualan per Produk</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-white border-b border-slate-100">
                <tr>
                    <th class="text-left py-3 px-4 font-semibold text-slate-600">Kode/SKU</th>
                    <th class="text-left py-3 px-4 font-semibold text-slate-600">Nama Produk</th>
                    <th class="text-left py-3 px-4 font-semibold text-slate-600">Kategori</th>
                    <th class="text-center py-3 px-4 font-semibold text-slate-600">Qty Terjual</th>
                    <th class="text-right py-3 px-4 font-semibold text-slate-600">Total Penjualan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data['items'] as $item)
                <tr class="border-b border-slate-50 hover:bg-slate-50/50">
                    <td class="py-3 px-4 font-mono text-xs text-slate-500">{{ $item->sku }}</td>
                    <td class="py-3 px-4 font-medium text-slate-700">{{ $item->name }}</td>
                    <td class="py-3 px-4 text-slate-600">{{ $item->category_name }}</td>
                    <td class="py-3 px-4 text-center font-bold text-slate-700">{{ $item->qty }}</td>
                    <td class="py-3 px-4 text-right font-semibold text-emerald-600">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="py-8 text-center text-slate-400">Tidak ada data penjualan pada kriteria ini.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4 border-t border-slate-100 overflow-x-auto">
        {{ $data['items']->links() }}
    </div>
</div>
</x-app-layout>
