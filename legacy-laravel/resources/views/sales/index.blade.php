<x-app-layout>
@section('title', 'Riwayat Penjualan')
<x-slot name="header"><h2 class="text-2xl font-bold text-slate-800">Riwayat Penjualan</h2></x-slot>
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div><label class="text-xs font-medium text-slate-500">Dari</label><input type="date" name="start_date" value="{{ $startDate }}" class="w-full mt-1 rounded-xl border-slate-200 text-sm"></div>
        <div><label class="text-xs font-medium text-slate-500">Sampai</label><input type="date" name="end_date" value="{{ $endDate }}" class="w-full mt-1 rounded-xl border-slate-200 text-sm"></div>
        @if(auth()->user()->hasRole('admin'))
        <div><label class="text-xs font-medium text-slate-500">Kasir</label><select name="user_id" class="w-full mt-1 rounded-xl border-slate-200 text-sm"><option value="">Semua</option>@foreach($cashiers as $c)<option value="{{ $c->id }}" {{ $userId == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach</select></div>
        @endif
        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-xl text-sm font-medium">Filter</button>
    </form>
</div>
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <!-- Mobile: card list -->
    <div class="md:hidden divide-y divide-slate-100">
        @forelse($sales as $sale)
        <div class="p-4">
            <div class="flex items-start justify-between gap-3 mb-2">
                <div class="min-w-0">
                    <p class="font-mono text-xs font-medium text-indigo-600">{{ $sale->invoice_number }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">{{ $sale->created_at->format('d/m/Y H:i') }} · {{ $sale->user->name }}</p>
                </div>
                <span class="shrink-0 px-2 py-0.5 rounded-full text-xs font-semibold {{ $sale->status === 'completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">{{ $sale->status === 'completed' ? 'Selesai' : 'Diretur' }}</span>
            </div>
            <div class="flex items-center justify-between mb-2">
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $sale->payment_method === 'qris' ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-600' }}">{{ $sale->payment_method === 'qris' ? 'QRIS' : 'Tunai' }}</span>
                <span class="text-xs text-slate-400">Rp {{ number_format($sale->bayar, 0, ',', '.') }}</span>
            </div>
            <div class="flex items-center justify-between">
                <p class="font-bold text-slate-800">Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</p>
                <div class="flex items-center gap-1">
                    <a href="{{ route('sales.show', $sale) }}" class="p-1.5 rounded-lg text-slate-400 hover:bg-indigo-50 hover:text-indigo-600" title="Detail"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></a>
                    <a href="{{ route('sales.receipt', $sale) }}" class="p-1.5 rounded-lg text-slate-400 hover:bg-emerald-50 hover:text-emerald-600" title="Cetak"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg></a>
                    @if(auth()->user()->hasRole('admin') && $sale->status === 'completed')
                    <a href="{{ route('sale-returns.create', $sale) }}" class="p-1.5 rounded-lg text-slate-400 hover:bg-amber-50 hover:text-amber-600" title="Retur"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3"/></svg></a>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="py-8 text-center text-slate-400">Belum ada transaksi.</div>
        @endforelse
    </div>

    <!-- Desktop: table -->
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100"><tr>
                <th class="text-left py-3 px-4 font-semibold text-slate-600">Invoice</th>
                <th class="text-left py-3 px-4 font-semibold text-slate-600">Tanggal</th>
                <th class="text-left py-3 px-4 font-semibold text-slate-600">Kasir</th>
                <th class="text-right py-3 px-4 font-semibold text-slate-600">Total</th>
                <th class="text-center py-3 px-4 font-semibold text-slate-600">Metode</th>
                <th class="text-center py-3 px-4 font-semibold text-slate-600">Status</th>
                <th class="text-center py-3 px-4 font-semibold text-slate-600">Aksi</th>
            </tr></thead>
            <tbody>
                @forelse($sales as $sale)
                <tr class="border-b border-slate-50 hover:bg-slate-50/50">
                    <td class="py-3 px-4 font-mono text-xs font-medium text-indigo-600">{{ $sale->invoice_number }}</td>
                    <td class="py-3 px-4 text-slate-600">{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                    <td class="py-3 px-4 text-slate-600">{{ $sale->user->name }}</td>
                    <td class="py-3 px-4 text-right font-semibold text-slate-700">Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</td>
                    <td class="py-3 px-4 text-center"><span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $sale->payment_method === 'qris' ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-600' }}">{{ $sale->payment_method === 'qris' ? 'QRIS' : 'Tunai' }}</span></td>
                    <td class="py-3 px-4 text-center"><span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $sale->status === 'completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">{{ $sale->status === 'completed' ? 'Selesai' : 'Diretur' }}</span></td>
                    <td class="py-3 px-4 text-center">
                        <div class="flex items-center justify-center gap-1">
                            <a href="{{ route('sales.show', $sale) }}" class="p-1.5 rounded-lg text-slate-400 hover:bg-indigo-50 hover:text-indigo-600" title="Detail"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></a>
                            <a href="{{ route('sales.receipt', $sale) }}" class="p-1.5 rounded-lg text-slate-400 hover:bg-emerald-50 hover:text-emerald-600" title="Cetak"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg></a>
                            @if(auth()->user()->hasRole('admin') && $sale->status === 'completed')
                            <a href="{{ route('sale-returns.create', $sale) }}" class="p-1.5 rounded-lg text-slate-400 hover:bg-amber-50 hover:text-amber-600" title="Retur"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3"/></svg></a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="py-8 text-center text-slate-400">Belum ada transaksi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 border-t border-slate-100">{{ $sales->appends(request()->query())->links() }}</div>
</div>
</x-app-layout>
