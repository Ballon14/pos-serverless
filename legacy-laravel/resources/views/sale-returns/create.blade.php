<x-app-layout>
@section('title', 'Proses Retur')
<x-slot name="header">
    <h2 class="text-2xl font-bold text-slate-800">Proses Retur: {{ $sale->invoice_number }}</h2>
</x-slot>

<div class="max-w-4xl" x-data="returnForm()">
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex gap-3 items-start mb-6">
        <svg class="w-5 h-5 text-amber-500 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
        <div class="text-sm text-amber-800">
            <p class="font-bold">Peringatan Retur</p>
            <p>Memproses retur akan secara otomatis:</p>
            <ul class="list-disc ml-4 mt-1">
                <li>Mengembalikan stok produk yang diretur.</li>
                <li>Mencatat pergerakan stok sebagai barang masuk (retur).</li>
                <li>Mengubah status transaksi menjadi "returned" jika semua item diretur.</li>
            </ul>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
        <form method="POST" action="{{ route('sale-returns.store', $sale) }}">
            @csrf
            
            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-700 mb-1">Alasan Retur <span class="text-red-500">*</span></label>
                <textarea name="alasan" rows="2" class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-200" required placeholder="Contoh: Barang cacat, expired, dll">{{ old('alasan') }}</textarea>
                @error('alasan') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <h3 class="text-lg font-semibold text-slate-800 mb-3">Pilih Item untuk Diretur</h3>
            <div class="border rounded-xl border-slate-200 overflow-hidden mb-6">
                <!-- Mobile: card list -->
                <div class="md:hidden divide-y divide-slate-200">
                    @foreach($sale->items as $index => $item)
                    <div class="p-4" x-data="{ selected: false, maxQty: {{ $item->qty }} }">
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-slate-700">{{ $item->product->name }}</p>
                                <p class="text-xs text-slate-400 mt-0.5">Qty beli: {{ $item->qty }}</p>
                            </div>
                            <label class="flex items-center gap-2 shrink-0 cursor-pointer select-none">
                                <input type="checkbox" x-model="selected" @change="updateSelection({{ $item->product_id }}, selected, $refs.qtyInput.value)" class="item-checkbox rounded border-slate-300 text-indigo-600">
                                <span class="text-sm text-slate-600">Retur</span>
                            </label>
                        </div>
                        <input type="number"
                            x-ref="qtyInput"
                            x-bind:disabled="!selected"
                            value="{{ $item->qty }}"
                            min="1"
                            max="{{ $item->qty }}"
                            class="w-full text-center rounded-lg border-slate-200 py-1.5 text-sm disabled:bg-slate-100"
                            @input="updateQty({{ $item->product_id }}, $event.target.value)">
                    </div>
                    @endforeach
                </div>

                <!-- Desktop: table -->
                <div class="hidden md:block overflow-x-auto">
    <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="w-10 px-4 text-center">
                                <input type="checkbox" @change="toggleAll" x-model="allSelected" class="rounded border-slate-300 text-indigo-600">
                            </th>
                            <th class="text-left py-2 px-3 font-medium text-slate-600">Produk</th>
                            <th class="text-center py-2 px-3 font-medium text-slate-600">Qty Beli</th>
                            <th class="text-center py-2 px-3 font-medium text-slate-600 w-32">Qty Diretur</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sale->items as $index => $item)
                        <tr class="border-b border-slate-100 last:border-0" x-data="{ selected: false, maxQty: {{ $item->qty }} }">
                            <td class="px-4 text-center">
                                <input type="checkbox" x-model="selected" @change="updateSelection({{ $item->product_id }}, selected, $refs.qtyInput.value)" class="item-checkbox rounded border-slate-300 text-indigo-600">
                            </td>
                            <td class="py-3 px-3">
                                <span class="font-medium text-slate-700 block">{{ $item->product->name }}</span>
                            </td>
                            <td class="py-3 px-3 text-center text-slate-600">{{ $item->qty }}</td>
                            <td class="py-2 px-3">
                                <input type="number" 
                                    x-ref="qtyInput"
                                    x-bind:disabled="!selected" 
                                    value="{{ $item->qty }}" 
                                    min="1" 
                                    max="{{ $item->qty }}" 
                                    class="w-full text-center rounded-lg border-slate-200 py-1.5 text-sm disabled:bg-slate-100" 
                                    @input="updateQty({{ $item->product_id }}, $event.target.value)">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
    </div>
            </div>
            
            <template x-for="(item, index) in selectedItems" :key="index">
                <div>
                    <input type="hidden" :name="`items[${index}][product_id]`" :value="item.product_id">
                    <input type="hidden" :name="`items[${index}][qty]`" :value="item.qty">
                </div>
            </template>
            
            @error('items') <p class="text-sm font-semibold text-red-500 mb-4">{{ $message }}</p> @enderror

            <div class="flex gap-3">
                <a href="{{ route('sales.index') }}" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-sm font-medium hover:bg-slate-200">Batal</a>
                <button type="submit" class="px-6 py-2 bg-gradient-to-r from-amber-500 to-amber-600 text-white rounded-xl text-sm font-semibold shadow-lg shadow-amber-500/30 hover:shadow-amber-500/50 transition-all" x-bind:disabled="selectedItems.length === 0" x-bind:class="{ 'opacity-50 cursor-not-allowed': selectedItems.length === 0 }">Proses Retur</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('returnForm', () => ({
        selectedItems: [],
        allSelected: false,

        toggleAll(event) {
            const checkboxes = document.querySelectorAll('.item-checkbox');
            checkboxes.forEach(cb => {
                if(cb.checked !== this.allSelected) {
                    cb.click();
                }
            });
        },

        updateSelection(productId, isSelected, qty) {
            if (isSelected) {
                const exists = this.selectedItems.find(i => i.product_id === productId);
                if (!exists) {
                    this.selectedItems.push({ product_id: productId, qty: qty });
                }
            } else {
                this.selectedItems = this.selectedItems.filter(i => i.product_id !== productId);
            }
            this.checkAllSelected();
        },

        updateQty(productId, qty) {
            const item = this.selectedItems.find(i => i.product_id === productId);
            if (item) {
                item.qty = qty;
            }
        },
        
        checkAllSelected() {
            const checkboxes = document.querySelectorAll('.item-checkbox');
            const total = checkboxes.length;
            const checked = document.querySelectorAll('.item-checkbox:checked').length;
            this.allSelected = total > 0 && total === checked;
        }
    }));
});
</script>
</x-app-layout>
