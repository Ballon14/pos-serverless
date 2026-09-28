<x-app-layout>
@section('title', 'Detail Transaksi')
<x-slot name="header"><h2 class="text-2xl font-bold text-slate-800">Detail Transaksi {{ $sale->invoice_number }}</h2></x-slot>
<div class="max-w-3xl">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm mb-6">
            <div><span class="text-slate-500 block">Invoice</span><span class="font-mono font-semibold text-indigo-600">{{ $sale->invoice_number }}</span></div>
            <div><span class="text-slate-500 block">Tanggal</span><span class="font-medium">{{ $sale->created_at->format('d/m/Y H:i') }}</span></div>
            <div><span class="text-slate-500 block">Kasir</span><span class="font-medium">{{ $sale->user->name }}</span></div>
            <div><span class="text-slate-500 block">Status</span><span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $sale->status === 'completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">{{ $sale->status === 'completed' ? 'Selesai' : 'Diretur' }}</span></div>
        </div>
        <div class="overflow-x-auto">
        <table class="w-full text-sm mb-4">
            <thead class="bg-slate-50"><tr>
                <th class="text-left py-2 px-3 font-medium text-slate-500">Produk</th>
                <th class="text-center py-2 px-3 font-medium text-slate-500">Qty</th>
                <th class="text-right py-2 px-3 font-medium text-slate-500">Harga</th>
                <th class="text-right py-2 px-3 font-medium text-slate-500">Subtotal</th>
            </tr></thead>
            <tbody>
                @foreach($sale->items as $item)
                <tr class="border-b border-slate-50">
                    <td class="py-2 px-3 font-medium text-slate-700">{{ $item->product->name }}</td>
                    <td class="py-2 px-3 text-center">{{ $item->qty }}</td>
                    <td class="py-2 px-3 text-right">Rp {{ number_format($item->harga, 0, ',', '.') }}</td>
                    <td class="py-2 px-3 text-right font-semibold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    </div>
        <div class="space-y-1 text-sm text-right">
            <div class="flex justify-end gap-8"><span class="text-slate-500">Subtotal</span><span class="font-medium w-32">Rp {{ number_format($sale->subtotal, 0, ',', '.') }}</span></div>
            <div class="flex justify-end gap-8"><span class="text-slate-500">Diskon</span><span class="font-medium w-32 text-red-600">- Rp {{ number_format($sale->diskon, 0, ',', '.') }}</span></div>
            <div class="flex justify-end gap-8 text-lg"><span class="font-semibold">Grand Total</span><span class="font-bold w-32">Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</span></div>
            <div class="flex justify-end gap-8"><span class="text-slate-500">Bayar</span><span class="font-medium w-32">Rp {{ number_format($sale->bayar, 0, ',', '.') }}</span></div>
            <div class="flex justify-end gap-8"><span class="text-slate-500">Kembalian</span><span class="font-medium w-32 text-emerald-600">Rp {{ number_format($sale->kembalian, 0, ',', '.') }}</span></div>
        </div>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('sales.index') }}" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-sm font-medium">← Kembali</a>
        <a href="{{ route('sales.receipt', $sale) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-xl text-sm font-semibold"><i class="fa-solid fa-print"></i> Cetak Struk</a>
    </div>
</x-app-layout>
