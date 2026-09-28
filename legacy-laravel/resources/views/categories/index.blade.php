<x-app-layout>
@section('title', 'Kategori Produk')
<x-slot name="header">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-2xl font-bold text-slate-800">Kategori Produk</h2>
        <a href="{{ route('categories.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl text-sm font-semibold shadow-lg shadow-indigo-500/30 hover:shadow-indigo-500/50 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Tambah Kategori
        </a>
    </div>
</x-slot>

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <!-- Mobile: card list -->
    <div class="md:hidden divide-y divide-slate-100">
        @forelse($categories as $i => $category)
        <div class="p-4">
            <div class="flex items-start justify-between gap-3 mb-2">
                <div class="min-w-0">
                    <p class="font-medium text-slate-700 truncate">{{ $category->name }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">{{ Str::limit($category->description, 60) }}</p>
                </div>
                <form method="POST" action="{{ route('categories.toggle-active', $category) }}" class="shrink-0" onsubmit="return confirmForm(this, 'Ubah status kategori ini?')">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold shadow-sm border {{ $category->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100 hover:border-emerald-300' : 'bg-red-50 text-red-700 border-red-200 hover:bg-red-100 hover:border-red-300' }} transition-all focus:outline-none focus:ring-2 focus:ring-offset-1 {{ $category->is_active ? 'focus:ring-emerald-500' : 'focus:ring-red-500' }}" title="Klik untuk mengubah status">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                        {{ $category->is_active ? 'Aktif' : 'Nonaktif' }}
                    </button>
                </form>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-xs text-slate-500"><span class="bg-indigo-100 text-indigo-700 text-xs font-bold px-2 py-0.5 rounded-full">{{ $category->products_count }}</span> produk</span>
                <div class="flex items-center gap-1">
                    <a href="{{ route('categories.edit', $category) }}" class="p-1.5 rounded-lg text-slate-400 hover:bg-indigo-50 hover:text-indigo-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></a>
                    <form method="POST" action="{{ route('categories.destroy', $category) }}" onsubmit="return confirmForm(this, 'Yakin hapus kategori ini?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="py-8 text-center text-slate-400">Belum ada kategori.</div>
        @endforelse
    </div>

    <!-- Desktop: table -->
    <div class="hidden md:block overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-100">
            <tr>
                <th class="text-left py-3 px-4 font-semibold text-slate-600">#</th>
                <th class="text-left py-3 px-4 font-semibold text-slate-600">Nama</th>
                <th class="text-left py-3 px-4 font-semibold text-slate-600">Deskripsi</th>
                <th class="text-center py-3 px-4 font-semibold text-slate-600">Produk</th>
                <th class="text-center py-3 px-4 font-semibold text-slate-600">Status</th>
                <th class="text-center py-3 px-4 font-semibold text-slate-600">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($categories as $i => $category)
            <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                <td class="py-3 px-4 text-slate-500">{{ $categories->firstItem() + $i }}</td>
                <td class="py-3 px-4 font-medium text-slate-700">{{ $category->name }}</td>
                <td class="py-3 px-4 text-slate-500">{{ Str::limit($category->description, 50) }}</td>
                <td class="py-3 px-4 text-center"><span class="bg-indigo-100 text-indigo-700 text-xs font-bold px-2 py-0.5 rounded-full">{{ $category->products_count }}</span></td>
                <td class="py-3 px-4 text-center">
                    <form method="POST" action="{{ route('categories.toggle-active', $category) }}" class="inline-block" onsubmit="return confirmForm(this, 'Ubah status kategori ini?')">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold shadow-sm border {{ $category->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100 hover:border-emerald-300' : 'bg-red-50 text-red-700 border-red-200 hover:bg-red-100 hover:border-red-300' }} transition-all focus:outline-none focus:ring-2 focus:ring-offset-1 {{ $category->is_active ? 'focus:ring-emerald-500' : 'focus:ring-red-500' }}" title="Klik untuk mengubah status">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                            {{ $category->is_active ? 'Aktif' : 'Nonaktif' }}
                        </button>
                    </form>
                </td>
                <td class="py-3 px-4 text-center">
                    <div class="flex items-center justify-center gap-1">
                        <a href="{{ route('categories.edit', $category) }}" class="p-1.5 rounded-lg text-slate-400 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </a>
                        <form method="POST" action="{{ route('categories.destroy', $category) }}" onsubmit="return confirmForm(this, 'Yakin hapus kategori ini?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="py-8 text-center text-slate-400">Belum ada kategori.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
    <div class="px-4 py-3 border-t border-slate-100">{{ $categories->links() }}</div>
</div>
</x-app-layout>
