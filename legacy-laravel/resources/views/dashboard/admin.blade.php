<x-app-layout>
@section('title', 'Dashboard')
@push('scripts')
@vite(['resources/js/dashboard.js'])
@endpush

<x-slot name="header">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-800">Dashboard</h2>
            <p class="text-sm text-slate-500 mt-0.5">Selamat {{ now()->hour < 12 ? 'pagi' : (now()->hour < 15 ? 'siang' : (now()->hour < 18 ? 'sore' : 'malam')) }}, {{ auth()->user()->name }} 👋</p>
        </div>
        <p class="text-xs text-slate-400 font-medium">{{ now()->translatedFormat('l, d F Y') }}</p>
    </div>
</x-slot>

<script type="application/json" id="daily-sales-data">@json($data['sales_chart'])</script>
<script type="application/json" id="top-products-data">@json($data['top_products']->map(fn ($item) => ['name' => $item->product?->name ?? 'Produk dihapus', 'total_qty' => (int) $item->total_qty, 'total_sales' => (float) $item->total_sales]))</script>

{{-- Alert Banner --}}
@if($data['low_stock_count'] > 0 || (config('app.attendance_enabled') && ($data['attendance_summary']['tidak_hadir'] ?? 0) > 0))
<div class="mb-6 rounded-2xl bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 overflow-hidden">
    <div class="p-4 sm:p-5">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-9 h-9 rounded-xl bg-amber-500 flex items-center justify-center shadow-md shadow-amber-500/30">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            </div>
            <h3 class="text-sm font-bold text-slate-800">Perlu Perhatian</h3>
        </div>
        <div class="space-y-2">
            @if($data['low_stock_count'] > 0)
            <a href="{{ route('stock.low') }}" class="flex items-center gap-3 bg-white/80 hover:bg-white rounded-xl border border-amber-200 p-3 transition-colors group">
                <span class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </span>
                <span class="flex-1 min-w-0 text-sm font-medium text-slate-700">{{ $data['low_stock_count'] }} produk stok menipis</span>
                <svg class="w-4 h-4 text-slate-400 group-hover:text-indigo-500 transition-colors shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
            @endif
            @if(config('app.attendance_enabled') && ($data['attendance_summary']['tidak_hadir'] ?? 0) > 0)
            <a href="{{ route('attendance.admin') }}" class="flex items-center gap-3 bg-white/80 hover:bg-white rounded-xl border border-amber-200 p-3 transition-colors group">
                <span class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
                <span class="flex-1 min-w-0 text-sm font-medium text-slate-700">{{ $data['attendance_summary']['tidak_hadir'] }} karyawan belum hadir</span>
                <svg class="w-4 h-4 text-slate-400 group-hover:text-indigo-500 transition-colors shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
            @endif
        </div>
    </div>
</div>
@endif

{{-- Stats Cards - 3 equal columns --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-5 mb-6">
    {{-- Penjualan Hari Ini --}}
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between">
            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-slate-500">Penjualan Hari Ini</p>
                <p class="text-2xl font-bold text-slate-800 mt-2 truncate">Rp {{ number_format($data['sales_today'], 0, ',', '.') }}</p>
                <p class="text-xs font-semibold text-emerald-600 mt-1">{{ $data['sales_count_today'] }} transaksi</p>
            </div>
            <div class="w-11 h-11 bg-emerald-100 rounded-xl flex items-center justify-center shrink-0 ml-3">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
    </div>

    {{-- Penjualan Bulan Ini --}}
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between">
            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-slate-500">Penjualan Bulan Ini</p>
                <p class="text-2xl font-bold text-slate-800 mt-2 truncate">Rp {{ number_format($data['sales_this_month'], 0, ',', '.') }}</p>
                <p class="text-xs font-semibold text-indigo-600 mt-1">{{ now()->translatedFormat('F Y') }}</p>
            </div>
            <div class="w-11 h-11 bg-indigo-100 rounded-xl flex items-center justify-center shrink-0 ml-3">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
        </div>
    </div>

    {{-- Stok Menipis --}}
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 hover:shadow-md transition-shadow {{ $data['low_stock_count'] > 0 ? 'border-red-200' : '' }}">
        <div class="flex items-start justify-between">
            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-slate-500">Stok Menipis</p>
                <p class="text-2xl font-bold text-slate-800 mt-2">{{ $data['low_stock_count'] }}</p>
                <p class="text-xs font-semibold mt-1 {{ $data['low_stock_count'] > 0 ? 'text-red-600' : 'text-emerald-600' }}">
                    {{ $data['low_stock_count'] > 0 ? 'Perlu restock' : 'Stok aman ✓' }}
                </p>
            </div>
            <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 ml-3 {{ $data['low_stock_count'] > 0 ? 'bg-red-100' : 'bg-slate-100' }}">
                <svg class="w-5 h-5 {{ $data['low_stock_count'] > 0 ? 'text-red-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
            </div>
        </div>
    </div>
</div>

{{-- Charts - Single Card with Divider --}}
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden mb-6">
    <div class="flex flex-col lg:flex-row lg:h-[320px]">
        {{-- Left: Sales Chart --}}
        <div class="flex-1 flex flex-col min-w-0">
            <div class="p-4 sm:p-5 border-b border-slate-100 shrink-0">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-indigo-100 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-800">Grafik Penjualan</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Total: <span id="sales-chart-total" class="font-bold text-emerald-600">-</span> · Rata-rata: <span id="sales-chart-average" class="font-bold text-slate-700">-</span></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-1 bg-slate-100 rounded-xl p-1" id="sales-chart-tabs">
                        <button type="button" data-period="7d" class="sales-tab px-3 py-1.5 rounded-lg text-xs font-semibold bg-white text-indigo-600 shadow-sm transition-all">7 Hari</button>
                        <button type="button" data-period="30d" class="sales-tab px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-500 hover:text-slate-700 transition-all">30 Hari</button>
                        <button type="button" data-period="12m" class="sales-tab px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-500 hover:text-slate-700 transition-all">Bulanan</button>
                    </div>
                </div>
            </div>
            <div class="p-4 sm:p-5 flex-1 min-h-0">
                <div class="relative w-full h-full min-h-[200px]">
                    <canvas id="daily-sales-chart"></canvas>
                </div>
            </div>
        </div>

        {{-- Divider --}}
        <div class="border-b lg:border-b-0 lg:border-r border-slate-100"></div>

        {{-- Right: Top Products --}}
        <div class="flex flex-col lg:w-[380px] shrink-0">
            <div class="p-4 sm:p-5 border-b border-slate-100 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-100 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">Produk Terlaris</h3>
                        <p class="text-xs text-slate-500">{{ now()->translatedFormat('F Y') }}</p>
                    </div>
                </div>
            </div>
            <div class="flex flex-col flex-1 min-h-0">
                {{-- Donut Chart --}}
                <div class="px-5 pt-3 shrink-0">
                    <div class="relative h-28 mx-auto max-w-[150px]">
                        <canvas id="top-products-chart"></canvas>
                    </div>
                </div>
                {{-- Ranked List --}}
                <div class="p-4 sm:p-5 flex-1 overflow-y-auto">
                    @php $maxQty = $data['top_products']->max('total_qty') ?: 1; @endphp
                    <div class="space-y-1.5">
                        @forelse($data['top_products'] as $i => $item)
                        <div class="flex items-center gap-2.5 p-2 rounded-xl hover:bg-slate-50 transition-colors">
                            <span class="w-6 h-6 rounded-md flex items-center justify-center text-white text-[10px] font-bold shrink-0 {{ $i === 0 ? 'bg-indigo-500' : ($i === 1 ? 'bg-purple-500' : ($i === 2 ? 'bg-amber-500' : ($i === 3 ? 'bg-emerald-500' : 'bg-sky-500'))) }}">{{ $i + 1 }}</span>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-baseline justify-between gap-2">
                                    <p class="text-xs font-semibold text-slate-700 truncate">{{ $item->product?->name ?? 'Produk dihapus' }}</p>
                                    <span class="text-xs font-bold text-slate-800 shrink-0">{{ $item->total_qty }}</span>
                                </div>
                                <div class="mt-1 flex items-center gap-2">
                                    <div class="flex-1 h-1 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full {{ $i === 0 ? 'bg-indigo-400' : ($i === 1 ? 'bg-purple-400' : ($i === 2 ? 'bg-amber-400' : ($i === 3 ? 'bg-emerald-400' : 'bg-sky-400'))) }}" style="width: {{ round(($item->total_qty / $maxQty) * 100) }}%"></div>
                                    </div>
                                    <span class="text-[10px] text-slate-400 shrink-0">Rp {{ number_format($item->total_sales / 1000, 0, ',', '.') }}rb</span>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-6">
                            <svg class="w-8 h-8 text-slate-200 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            <p class="text-xs text-slate-400">Belum ada data</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Low Stock Table --}}
@if($data['low_stock']->count() > 0)
<div class="bg-white rounded-2xl shadow-sm border border-red-100 overflow-hidden">
    <div class="p-4 sm:p-5 border-b border-slate-100 bg-red-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-red-100 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-800">Stok Menipis</h3>
                <p class="text-xs text-slate-500">{{ $data['low_stock']->count() }} produk perlu restock segera</p>
            </div>
        </div>
        <a href="{{ route('stock.low') }}" class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-red-600 text-white text-xs font-semibold rounded-xl shadow-lg shadow-red-500/30 hover:bg-red-700 transition-colors shrink-0">
            Kelola Restock →
        </a>
    </div>
    {{-- Mobile cards --}}
    <div class="sm:hidden divide-y divide-slate-100">
        @foreach($data['low_stock'] as $prod)
        <div class="p-4 flex items-center justify-between gap-3">
            <div class="min-w-0">
                <p class="text-sm font-medium text-slate-700 truncate">{{ $prod->name }}</p>
                <p class="text-xs text-slate-400 mt-0.5">{{ $prod->category->name }} · Min: {{ $prod->min_stok }}</p>
            </div>
            <span class="shrink-0 px-2.5 py-1 rounded-full text-xs font-bold {{ $prod->stok <= 0 ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }}">{{ $prod->stok }}</span>
        </div>
        @endforeach
    </div>
    {{-- Desktop table --}}
    <div class="hidden sm:block overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50/50">
                    <th class="text-left py-2.5 px-4 font-semibold text-slate-500 text-xs">Produk</th>
                    <th class="text-left py-2.5 px-4 font-semibold text-slate-500 text-xs">Kategori</th>
                    <th class="text-center py-2.5 px-4 font-semibold text-slate-500 text-xs">Stok</th>
                    <th class="text-center py-2.5 px-4 font-semibold text-slate-500 text-xs">Min. Stok</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['low_stock'] as $prod)
                <tr class="border-b border-slate-50 hover:bg-slate-50/50">
                    <td class="py-2.5 px-4 font-medium text-slate-700">{{ $prod->name }}</td>
                    <td class="py-2.5 px-4 text-slate-500">{{ $prod->category->name }}</td>
                    <td class="py-2.5 px-4 text-center"><span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $prod->stok <= 0 ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }}">{{ $prod->stok }}</span></td>
                    <td class="py-2.5 px-4 text-center text-slate-400">{{ $prod->min_stok }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

</x-app-layout>
