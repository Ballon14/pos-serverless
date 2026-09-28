<x-app-layout>
@section('title', 'Detail Retur')
<x-slot name="header">
    <h2 class="text-2xl font-bold text-slate-800">Detail Retur: {{ $saleReturn->return_number }}</h2>
</x-slot>

<div class="max-w-4xl">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-sm mb-8">
            <div>
                <span class="text-slate-500 block mb-1">No. Retur</span>
                <span class="font-mono font-semibold text-amber-600 bg-amber-50 px-2 py-1 rounded-md">{{ $saleReturn->return_number }}</span>
            </div>
            <div>
                <span class="text-slate-500 block mb-1">Tanggal Retur</span>
                <span class="font-medium text-slate-800">{{ $saleReturn->created_at->format('d/m/Y H:i') }}</span>
            </div>
            <div>
                <span class="text-slate-500 block mb-1">Invoice Asli</span>
                <a href="{{ route('sales.show', $saleReturn->sale_id) }}" class="font-mono font-semibold text-indigo-600 hover:underline">{{ $saleReturn->sale->invoice_number }}</a>
            </div>
            <div>
                <span class="text-slate-500 block mb-1">Diproses Oleh</span>
                <span class="font-medium text-slate-800">{{ $saleReturn->processedBy->name }}</span>
            </div>
        </div>

        <div class="mb-6 bg-slate-50 p-4 rounded-xl text-sm text-slate-700">
            <span class="font-medium block mb-1 text-slate-800">Alasan Retur:</span>
            {{ $saleReturn->alasan }}
        </div>

        <h3 class="text-lg font-semibold text-slate-800 mb-3">Item yang Diretur</h3>
        <div class="border rounded-xl border-slate-200 overflow-hidden mb-6">
            <div class="overflow-x-auto">
    <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="text-left py-3 px-4 font-medium text-slate-600">Produk</th>
                        <th class="text-center py-3 px-4 font-medium text-slate-600 w-32">Qty Diretur</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($saleReturn->items as $item)
                    <tr class="border-b border-slate-100 last:border-0">
                        <td class="py-3 px-4">
                            <span class="font-medium text-slate-700 block">{{ $item->product->name }}</span>
                            <span class="text-xs text-slate-400 font-mono">{{ $item->product->sku }}</span>
                        </td>
                        <td class="py-3 px-4 text-center font-bold text-amber-600">{{ $item->qty }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
    </div>
        </div>

        <div class="flex gap-3">
            <a href="{{ route('sale-returns.index') }}" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-sm font-medium hover:bg-slate-200">← Kembali</a>
        </div>
    </div>
</div>
</x-app-layout>
