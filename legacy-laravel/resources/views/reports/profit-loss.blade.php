<x-app-layout>
@section('title', 'Laporan Laba Rugi')
<x-slot name="header">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl sm:text-2xl font-bold text-slate-800">Laporan Laba Rugi</h2>
        <form method="GET" action="{{ route('reports.profit-loss') }}" target="_blank" class="w-full sm:w-auto">
            <input type="hidden" name="start_date" value="{{ $startDate }}">
            <input type="hidden" name="end_date" value="{{ $endDate }}">
            <input type="hidden" name="export" value="pdf">
            <button type="submit" class="w-full sm:w-auto justify-center inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl text-sm font-semibold shadow-lg shadow-red-500/30 hover:shadow-red-500/50 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export PDF
            </button>
        </form>
    </div>
</x-slot>

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
        <button type="submit" class="w-full sm:w-auto px-4 py-2 mt-2 sm:mt-0 bg-indigo-600 text-white rounded-xl text-sm font-medium">Tampilkan</button>
    </form>
</div>

<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-6 border-b border-slate-100 bg-slate-50/50 text-center">
            <h3 class="text-xl font-bold text-slate-800">Laporan Laba Rugi</h3>
            <p class="text-sm text-slate-500 mt-1">Periode: {{ \Carbon\Carbon::parse($startDate)->translatedFormat('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->translatedFormat('d M Y') }}</p>
        </div>
        
        <div class="p-8">
            <!-- Pendapatan -->
            <div class="mb-8">
                <h4 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-4 border-b border-slate-200 pb-2">Pendapatan</h4>
                <div class="space-y-3">
                    <div class="flex justify-between items-center text-slate-700">
                        <span>Penjualan Kotor</span>
                        <span>Rp {{ number_format($data['revenue'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center text-slate-700">
                        <span>Diskon Penjualan</span>
                        <span class="text-red-500">- Rp {{ number_format($data['discounts'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center font-semibold text-slate-800 pt-3 border-t border-slate-100">
                        <span>Penjualan Bersih</span>
                        <span>Rp {{ number_format($data['net_revenue'], 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- Harga Pokok Penjualan -->
            <div class="mb-8">
                <h4 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-4 border-b border-slate-200 pb-2">Harga Pokok Penjualan (HPP)</h4>
                <div class="space-y-3">
                    <div class="flex justify-between items-center text-slate-700">
                        <span>Total HPP Barang Terjual</span>
                        <span>Rp {{ number_format($data['cogs'], 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- Laba Kotor -->
            <div class="bg-slate-50 rounded-2xl p-6 border border-slate-100">
                <div class="flex justify-between items-center">
                    <span class="text-lg font-bold text-slate-800">Laba Kotor</span>
                    <span class="text-2xl font-black {{ $data['gross_profit'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                        Rp {{ number_format($data['gross_profit'], 0, ',', '.') }}
                    </span>
                </div>
                <p class="text-sm text-slate-500 mt-2">Margin Laba Kotor: 
                    <span class="font-semibold">{{ $data['net_revenue'] > 0 ? round(($data['gross_profit'] / $data['net_revenue']) * 100, 2) : 0 }}%</span>
                </p>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
