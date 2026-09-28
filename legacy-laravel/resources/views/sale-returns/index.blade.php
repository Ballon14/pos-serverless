<x-app-layout>
@section('title', 'Retur Penjualan')
<x-slot name="header">
    <h2 class="text-2xl font-bold text-slate-800">Retur Penjualan</h2>
</x-slot>

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <!-- Mobile: card list -->
    <div class="md:hidden divide-y divide-slate-100">
        @forelse($returns as $return)
        <div class="p-4">
            <div class="flex items-start justify-between gap-3 mb-2">
                <div class="min-w-0">
                    <p class="font-mono text-xs font-medium text-amber-600">{{ $return->return_number }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">{{ $return->created_at->format('d/m/Y H:i') }} · {{ $return->processedBy->name }}</p>
                </div>
                <a href="{{ route('sale-returns.show', $return) }}" class="shrink-0 p-1.5 rounded-lg text-slate-400 hover:bg-indigo-50 hover:text-indigo-600" title="Detail"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></a>
            </div>
            <p class="text-xs mb-2">
                <span class="text-slate-500">Invoice: </span>
                <a href="{{ route('sales.show', $return->sale_id) }}" class="font-mono font-medium text-indigo-600 hover:underline">{{ $return->sale->invoice_number }}</a>
            </p>
            <p class="text-xs text-slate-500">{{ Str::limit($return->alasan, 60) }}</p>
        </div>
        @empty
        <div class="py-8 text-center text-slate-400">Belum ada data retur penjualan.</div>
        @endforelse
    </div>

    <!-- Desktop: table -->
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="text-left py-3 px-4 font-semibold text-slate-600">No. Retur</th>
                    <th class="text-left py-3 px-4 font-semibold text-slate-600">Tanggal</th>
                    <th class="text-left py-3 px-4 font-semibold text-slate-600">Invoice Penjualan</th>
                    <th class="text-left py-3 px-4 font-semibold text-slate-600">Diproses Oleh</th>
                    <th class="text-left py-3 px-4 font-semibold text-slate-600">Alasan</th>
                    <th class="text-center py-3 px-4 font-semibold text-slate-600">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($returns as $return)
                <tr class="border-b border-slate-50 hover:bg-slate-50/50">
                    <td class="py-3 px-4">
                        <span class="font-mono text-xs font-medium text-amber-600 block">{{ $return->return_number }}</span>
                    </td>
                    <td class="py-3 px-4 text-slate-600">{{ $return->created_at->format('d/m/Y H:i') }}</td>
                    <td class="py-3 px-4">
                        <a href="{{ route('sales.show', $return->sale_id) }}" class="font-mono text-xs font-medium text-indigo-600 hover:underline">{{ $return->sale->invoice_number }}</a>
                    </td>
                    <td class="py-3 px-4 text-slate-600">{{ $return->processedBy->name }}</td>
                    <td class="py-3 px-4 text-slate-500">{{ Str::limit($return->alasan, 30) }}</td>
                    <td class="py-3 px-4 text-center">
                        <a href="{{ route('sale-returns.show', $return) }}" class="inline-flex p-1.5 rounded-lg text-slate-400 hover:bg-indigo-50 hover:text-indigo-600 transition-colors" title="Detail">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-8 text-center text-slate-400">Belum ada data retur penjualan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 border-t border-slate-100">{{ $returns->links() }}</div>
</div>
</x-app-layout>
