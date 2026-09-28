<x-app-layout>
@section('title', 'Rekap Perubahan Harga Beli')
<x-slot name="header">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl sm:text-2xl font-bold text-slate-800">Rekap Perubahan Harga Beli</h2>
        <form method="GET" action="{{ route('reports.price-change') }}" target="_blank" class="w-full sm:w-auto">
            <input type="hidden" name="start_date" value="{{ $startDate }}">
            <input type="hidden" name="end_date" value="{{ $endDate }}">
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
        <div class="w-full sm:flex-1 sm:min-w-[200px]">
            <label class="text-xs font-medium text-slate-500">Produk</label>
            <select name="product_id" class="w-full mt-1 rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-200">
                <option value="">Semua Produk</option>
                @foreach($products as $p)
                <option value="{{ $p->id }}" {{ $productId == $p->id ? 'selected' : '' }}>{{ $p->sku }} - {{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="w-full sm:w-auto px-4 py-2 mt-2 sm:mt-0 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700 transition-colors">Tampilkan</button>
    </form>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-6">
        <p class="text-sm font-medium text-indigo-700 mb-1">Total Perubahan</p>
        <p class="text-3xl font-bold text-indigo-900">{{ $data['summary']['total_changes'] }}</p>
    </div>
    <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-6">
        <div class="flex items-center gap-1.5 mb-1">
            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
            <p class="text-sm font-medium text-emerald-700">Harga Naik</p>
        </div>
        <p class="text-3xl font-bold text-emerald-900">{{ $data['summary']['total_naik'] }}</p>
    </div>
    <div class="bg-red-50 border border-red-100 rounded-2xl p-6">
        <div class="flex items-center gap-1.5 mb-1">
            <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
            <p class="text-sm font-medium text-red-700">Harga Turun</p>
        </div>
        <p class="text-3xl font-bold text-red-900">{{ $data['summary']['total_turun'] }}</p>
    </div>
    <div class="bg-purple-50 border border-purple-100 rounded-2xl p-6">
        <p class="text-sm font-medium text-purple-700 mb-1">Produk Terpengaruh</p>
        <p class="text-3xl font-bold text-purple-900">{{ $data['summary']['products_affected'] }}</p>
    </div>
</div>

<!-- Current vs Last Bought Price (Alert) -->
@if($data['current_vs_last_bought']->isNotEmpty())
<div class="bg-white rounded-2xl shadow-lg shadow-amber-500/10 border-l-4 border-l-amber-500 border-y border-r border-slate-100 p-5 mb-8">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
        <div class="flex items-start gap-3">
            <div class="p-2.5 bg-amber-50 text-amber-600 rounded-xl shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.072 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
            </div>
            <div>
                <h3 class="font-bold text-slate-800 text-lg tracking-tight">Harga Aktual (Restock) Berbeda dengan Master Data</h3>
                <p class="text-xs text-slate-500 mt-0.5">Produk berikut baru saja dibeli dengan harga yang berbeda dari harga standar (Master Data) di sistem.</p>
            </div>
        </div>
        {{-- Bulk sync button --}}
        <form method="POST" action="{{ route('reports.sync-master-price') }}" id="bulk-sync-form" onsubmit="return confirmForm(this, 'Update harga beli {{ $data['current_vs_last_bought']->count() }} produk ke harga restock terakhir?', { title: 'Sync Semua Harga', confirmText: 'Ya, Update Semua' })">
            @csrf
            @foreach($data['current_vs_last_bought'] as $item)
                <input type="hidden" name="product_ids[]" value="{{ $item->product_id }}">
            @endforeach
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-indigo-500 to-indigo-600 text-white rounded-xl text-sm font-semibold shadow-lg shadow-indigo-500/30 hover:shadow-indigo-500/50 transition-all whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Sync Semua ({{ $data['current_vs_last_bought']->count() }})
            </button>
        </form>
    </div>
    <div class="overflow-x-auto rounded-xl border border-slate-100">
        <table class="w-full text-sm">
            <thead class="bg-slate-50">
                <tr class="border-b border-slate-200">
                    <th class="text-left py-3 px-4 font-semibold text-slate-600 text-xs">Produk</th>
                    <th class="text-right py-3 px-4 font-semibold text-slate-600 text-xs">Harga Master (Sistem)</th>
                    <th class="text-right py-3 px-4 font-semibold text-slate-600 text-xs">Harga Aktual (Restock)</th>
                    <th class="text-right py-3 px-4 font-semibold text-slate-600 text-xs">Selisih</th>
                    <th class="text-center py-3 px-4 font-semibold text-slate-600 text-xs">Status</th>
                    <th class="text-center py-3 px-4 font-semibold text-slate-600 text-xs">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['current_vs_last_bought'] as $item)
                <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                    <td class="py-3 px-4">
                        <span class="font-medium text-slate-700 block">{{ $item->product_name }}</span>
                        <span class="text-xs text-slate-400 font-mono">{{ $item->product_sku }}</span>
                    </td>
                    <td class="py-3 px-4 text-right text-slate-500">Rp {{ number_format($item->harga_beli_sekarang, 0, ',', '.') }}</td>
                    <td class="py-3 px-4 text-right font-semibold text-slate-800">Rp {{ number_format($item->harga_terakhir_dibeli, 0, ',', '.') }}</td>
                    <td class="py-3 px-4 text-right font-bold {{ $item->tipe === 'naik' ? 'text-emerald-600' : 'text-red-600' }}">
                        {{ $item->tipe === 'naik' ? '+' : '' }}Rp {{ number_format($item->selisih, 0, ',', '.') }}
                        <span class="text-xs font-normal">({{ $item->tipe === 'naik' ? '+' : '' }}{{ $item->persen }}%)</span>
                    </td>
                    <td class="py-3 px-4 text-center">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold {{ $item->tipe === 'naik' ? 'bg-emerald-100/80 text-emerald-700 border border-emerald-200' : 'bg-red-100/80 text-red-700 border border-red-200' }}">
                            @if($item->tipe === 'naik')
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                            Naik
                            @else
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                            Turun
                            @endif
                        </span>
                    </td>
                    <td class="py-3 px-4 text-center">
                        <form method="POST" action="{{ route('reports.sync-master-price') }}" class="inline" onsubmit="return confirmForm(this, 'Update harga beli &quot;{{ $item->product_name }}&quot; dari Rp {{ number_format($item->harga_beli_sekarang, 0, ',', '.') }} → Rp {{ number_format($item->harga_terakhir_dibeli, 0, ',', '.') }}?', { title: 'Update Harga Master', confirmText: 'Ya, Update' })">
                            @csrf
                            <input type="hidden" name="product_ids[]" value="{{ $item->product_id }}">
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-lg text-xs font-semibold hover:bg-indigo-100 hover:border-indigo-300 transition-all" title="Update harga master ke harga restock">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Sync
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif


<!-- Riwayat Perubahan Harga -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="p-4 border-b border-slate-100 bg-slate-50">
        <h3 class="font-semibold text-slate-700">Riwayat Perubahan Harga Beli</h3>
        <p class="text-xs text-slate-500 mt-0.5">Semua perubahan harga beli produk, baik dari restock maupun edit manual</p>
    </div>

    <!-- Mobile: card list -->
    <div class="md:hidden divide-y divide-slate-100">
        @forelse($data['changes'] as $change)
        <div class="p-4">
            <div class="flex items-start justify-between gap-3 mb-2">
                <div class="min-w-0">
                    <p class="font-medium text-slate-700 truncate">{{ $change->product_name }}</p>
                    <p class="font-mono text-xs text-slate-400 mt-0.5">{{ $change->product_sku }} · {{ $change->category_name }}</p>
                </div>
                <span class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold {{ $change->tipe === 'naik' ? 'bg-emerald-100/80 text-emerald-700 border border-emerald-200' : 'bg-red-100/80 text-red-700 border border-red-200' }}">
                    @if($change->tipe === 'naik')
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                    +{{ $change->persen }}%
                    @else
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                    {{ $change->persen }}%
                    @endif
                </span>
            </div>
            <div class="flex items-center gap-3 text-sm mb-1.5">
                <span class="text-slate-500 line-through">Rp {{ number_format($change->harga_lama, 0, ',', '.') }}</span>
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                <span class="font-bold {{ $change->tipe === 'naik' ? 'text-emerald-600' : 'text-red-600' }}">Rp {{ number_format($change->harga_baru, 0, ',', '.') }}</span>
            </div>
            <div class="flex items-center justify-between text-xs text-slate-400">
                <span>{{ $change->tanggal->format('d/m/Y') }}</span>
                @php
                    $badgeClass = match($change->sumber) {
                        'Restock' => 'bg-indigo-100 text-indigo-700',
                        'Sync Master' => 'bg-teal-100 text-teal-700',
                        default => 'bg-amber-100 text-amber-700',
                    };
                @endphp
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $badgeClass }}">{{ $change->sumber }}</span>
                <span>{{ $change->pencatat }}</span>
            </div>
            @if($change->purchase_id)
            <div class="mt-1.5">
                <a href="{{ route('purchases.show', $change->purchase_id) }}" class="inline-flex items-center gap-1 text-xs text-indigo-600 hover:text-indigo-800 font-medium hover:underline transition-colors">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    {{ $change->invoice_perubahan }}
                </a>
            </div>
            @endif
        </div>
        @empty
        <div class="py-8 text-center text-slate-400">Tidak ada perubahan harga pada periode ini.</div>
        @endforelse
    </div>

    <!-- Desktop: table -->
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="text-left py-3 px-4 font-semibold text-slate-600">Tanggal</th>
                    <th class="text-left py-3 px-4 font-semibold text-slate-600">Produk</th>
                    <th class="text-left py-3 px-4 font-semibold text-slate-600">Kategori</th>
                    <th class="text-right py-3 px-4 font-semibold text-slate-600">Harga Beli Lama</th>
                    <th class="text-right py-3 px-4 font-semibold text-slate-600">Harga Beli Baru</th>
                    <th class="text-right py-3 px-4 font-semibold text-slate-600">Selisih</th>
                    <th class="text-center py-3 px-4 font-semibold text-slate-600">Status</th>
                    <th class="text-center py-3 px-4 font-semibold text-slate-600">Sumber</th>
                    <th class="text-left py-3 px-4 font-semibold text-slate-600">Invoice/Ref</th>
                    <th class="text-left py-3 px-4 font-semibold text-slate-600">Pencatat</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data['changes'] as $change)
                <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                    <td class="py-3 px-4 text-slate-600 whitespace-nowrap">{{ $change->tanggal->format('d/m/Y') }}</td>
                    <td class="py-3 px-4">
                        <span class="font-medium text-slate-700 block">{{ $change->product_name }}</span>
                        <span class="text-xs text-slate-400 font-mono">{{ $change->product_sku }}</span>
                    </td>
                    <td class="py-3 px-4 text-slate-600">{{ $change->category_name }}</td>
                    <td class="py-3 px-4 text-right text-slate-500 line-through">Rp {{ number_format($change->harga_lama, 0, ',', '.') }}</td>
                    <td class="py-3 px-4 text-right font-semibold text-slate-800">Rp {{ number_format($change->harga_baru, 0, ',', '.') }}</td>
                    <td class="py-3 px-4 text-right font-bold {{ $change->tipe === 'naik' ? 'text-emerald-600' : 'text-red-600' }}">
                        {{ $change->tipe === 'naik' ? '+' : '' }}Rp {{ number_format($change->selisih, 0, ',', '.') }}
                        <span class="text-xs font-normal block">({{ $change->tipe === 'naik' ? '+' : '' }}{{ $change->persen }}%)</span>
                    </td>
                    <td class="py-3 px-4 text-center">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold {{ $change->tipe === 'naik' ? 'bg-emerald-100/80 text-emerald-700 border border-emerald-200' : 'bg-red-100/80 text-red-700 border border-red-200' }}">
                            @if($change->tipe === 'naik')
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                            Naik
                            @else
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                            Turun
                            @endif
                        </span>
                    </td>
                    <td class="py-3 px-4 text-center">
                        @php
                            $badgeClass = match($change->sumber) {
                                'Restock' => 'bg-indigo-50 text-indigo-700 border border-indigo-200',
                                'Sync Master' => 'bg-teal-50 text-teal-700 border border-teal-200',
                                default => 'bg-amber-50 text-amber-700 border border-amber-200',
                            };
                        @endphp
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold {{ $badgeClass }}">
                            {{ $change->sumber }}
                        </span>
                    </td>
                    <td class="py-3 px-4 text-xs whitespace-nowrap">
                        @if($change->purchase_id)
                            <a href="{{ route('purchases.show', $change->purchase_id) }}" class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800 font-medium hover:underline transition-colors">
                                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                {{ $change->invoice_perubahan }}
                            </a>
                        @else
                            <span class="text-slate-400">{{ $change->invoice_perubahan }}</span>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-slate-600 text-xs">{{ $change->pencatat }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="py-8 text-center text-slate-400">Tidak ada perubahan harga pada periode ini.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 border-t border-slate-100">{{ $data['changes']->appends(request()->query())->links() }}</div>
</div>
</x-app-layout>
