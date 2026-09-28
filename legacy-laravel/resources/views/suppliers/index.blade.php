<x-app-layout>
@section('title', 'Supplier')
<x-slot name="header">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-2xl font-bold text-slate-800">Supplier</h2>
        <a href="{{ route('suppliers.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl text-sm font-semibold shadow-lg shadow-indigo-500/30 hover:shadow-indigo-500/50 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Tambah Supplier
        </a>
    </div>
</x-slot>
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <!-- Mobile: card list -->
    <div class="md:hidden divide-y divide-slate-100">
        @forelse($suppliers as $i => $supplier)
        <div class="p-4">
            <div class="flex items-start justify-between gap-3 mb-2">
                <div class="min-w-0">
                    <p class="font-medium text-slate-700 truncate">{{ $supplier->name }}</p>
                    <p class="font-mono text-xs text-slate-400 mt-0.5">{{ $supplier->code }}</p>
                </div>
                <div class="flex items-center gap-1 shrink-0">
                    <a href="{{ route('suppliers.edit', $supplier) }}" class="p-1.5 rounded-lg text-slate-400 hover:bg-indigo-50 hover:text-indigo-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></a>
                    <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" onsubmit="return confirmForm(this, 'Yakin hapus pemasok ini?')">
                        @csrf @method('DELETE')
                        <button class="p-1.5 rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                    </form>
                </div>
            </div>
            <div class="flex items-center gap-4 text-xs text-slate-500">
                <span class="inline-flex items-center gap-1"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>{{ $supplier->phone ?? '-' }}</span>
                <span>{{ $supplier->contact_person ?? '-' }}</span>
            </div>
        </div>
        @empty
        <div class="py-8 text-center text-slate-400">Belum ada supplier.</div>
        @endforelse
    </div>

    <!-- Desktop: table -->
    <div class="hidden md:block overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-100">
            <tr>
                <th class="text-left py-3 px-4 font-semibold text-slate-600">#</th>
                <th class="text-left py-3 px-4 font-semibold text-slate-600">Kode</th>
                <th class="text-left py-3 px-4 font-semibold text-slate-600">Nama</th>
                <th class="text-left py-3 px-4 font-semibold text-slate-600">Telepon</th>
                <th class="text-left py-3 px-4 font-semibold text-slate-600">Kontak</th>
                <th class="text-center py-3 px-4 font-semibold text-slate-600">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($suppliers as $i => $supplier)
            <tr class="border-b border-slate-50 hover:bg-slate-50/50">
                <td class="py-3 px-4 text-slate-500">{{ $suppliers->firstItem() + $i }}</td>
                <td class="py-3 px-4 font-mono text-xs text-slate-600">{{ $supplier->code }}</td>
                <td class="py-3 px-4 font-medium text-slate-700">{{ $supplier->name }}</td>
                <td class="py-3 px-4 text-slate-600">{{ $supplier->phone ?? '-' }}</td>
                <td class="py-3 px-4 text-slate-600">{{ $supplier->contact_person ?? '-' }}</td>
                <td class="py-3 px-4 text-center">
                    <div class="flex items-center justify-center gap-1">
                        <a href="{{ route('suppliers.edit', $supplier) }}" class="p-1.5 rounded-lg text-slate-400 hover:bg-indigo-50 hover:text-indigo-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></a>
                        <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" onsubmit="return confirmForm(this, 'Yakin hapus pemasok ini?')">@csrf @method('DELETE')<button class="p-1.5 rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="py-8 text-center text-slate-400">Belum ada supplier.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
    <div class="px-4 py-3 border-t border-slate-100">{{ $suppliers->links() }}</div>
</div>
</x-app-layout>
