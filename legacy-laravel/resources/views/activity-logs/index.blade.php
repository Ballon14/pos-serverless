<x-app-layout>
@section('title', 'Log Aktivitas')
<x-slot name="header"><h2 class="text-2xl font-bold text-slate-800">Log Aktivitas</h2></x-slot>

@php
    $badge = [
        'auth.login' => 'bg-indigo-100 text-indigo-700',
        'auth.login_failed' => 'bg-red-100 text-red-700',
        'auth.logout' => 'bg-slate-100 text-slate-700',
        'sale.create' => 'bg-emerald-100 text-emerald-700',
        'sale.return' => 'bg-amber-100 text-amber-700',
        'purchase.create' => 'bg-teal-100 text-teal-700',
        'attendance.clock_in' => 'bg-indigo-100 text-indigo-700',
        'attendance.clock_out' => 'bg-indigo-100 text-indigo-700',
        'leave.create' => 'bg-purple-100 text-purple-700',
        'leave.approve' => 'bg-emerald-100 text-emerald-700',
        'leave.reject' => 'bg-red-100 text-red-700',
        'settings.backup' => 'bg-emerald-100 text-emerald-700',
        'settings.backup-csv' => 'bg-teal-100 text-teal-700',
        'settings.restore' => 'bg-red-100 text-red-700',
        'settings.restore-csv' => 'bg-amber-100 text-amber-700',
        'product.import' => 'bg-indigo-100 text-indigo-700',
        'product.export' => 'bg-indigo-100 text-indigo-700',
        'product.create' => 'bg-emerald-100 text-emerald-700',
        'product.update' => 'bg-blue-100 text-blue-700',
        'product.delete' => 'bg-red-100 text-red-700',
        'category.create' => 'bg-emerald-100 text-emerald-700',
        'category.update' => 'bg-blue-100 text-blue-700',
        'category.delete' => 'bg-red-100 text-red-700',
        'category.toggle_active' => 'bg-amber-100 text-amber-700',
        'supplier.create' => 'bg-emerald-100 text-emerald-700',
        'supplier.update' => 'bg-blue-100 text-blue-700',
        'supplier.delete' => 'bg-red-100 text-red-700',
        'profile.update' => 'bg-slate-100 text-slate-700',
        'password.update' => 'bg-slate-100 text-slate-700',
    ];
    $label = [
        'auth.login' => 'Login',
        'auth.login_failed' => 'Login Gagal',
        'auth.logout' => 'Logout',
        'sale.create' => 'Penjualan',
        'sale.return' => 'Retur',
        'purchase.create' => 'Pembelian',
        'attendance.clock_in' => 'Clock In',
        'attendance.clock_out' => 'Clock Out',
        'leave.create' => 'Izin/Cuti',
        'leave.approve' => 'Izin Disetujui',
        'leave.reject' => 'Izin Ditolak',
        'settings.backup' => 'Backup SQL',
        'settings.backup-csv' => 'Backup CSV',
        'settings.restore' => 'Restore SQL',
        'settings.restore-csv' => 'Restore CSV',
        'product.import' => 'Import Produk',
        'product.export' => 'Export Produk',
        'product.create' => 'Tambah Produk',
        'product.update' => 'Edit Produk',
        'product.delete' => 'Hapus Produk',
        'category.create' => 'Tambah Kategori',
        'category.update' => 'Edit Kategori',
        'category.delete' => 'Hapus Kategori',
        'category.toggle_active' => 'Status Kategori',
        'supplier.create' => 'Tambah Supplier',
        'supplier.update' => 'Edit Supplier',
        'supplier.delete' => 'Hapus Supplier',
        'profile.update' => 'Update Profil',
        'password.update' => 'Update Password',
    ];
@endphp

<!-- Stats -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Log</p>
        <p class="text-2xl font-bold text-slate-800 mt-1">{{ number_format($stats['total'], 0, ',', '.') }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Aktivitas Hari Ini</p>
        <p class="text-2xl font-bold text-indigo-600 mt-1">{{ number_format($stats['today'], 0, ',', '.') }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Login Gagal</p>
        <p class="text-2xl font-bold text-red-500 mt-1">{{ number_format($stats['failed_login'], 0, ',', '.') }}</p>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div><label class="text-xs font-medium text-slate-500">Pengguna</label><select name="user_id" class="w-full mt-1 rounded-xl border-slate-200 text-sm"><option value="">Semua</option>@foreach($users as $u)<option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>@endforeach</select></div>
        <div><label class="text-xs font-medium text-slate-500">Jenis Aksi</label><select name="action" class="w-full mt-1 rounded-xl border-slate-200 text-sm"><option value="">Semua</option>@foreach($actions as $a)<option value="{{ $a }}" {{ request('action') == $a ? 'selected' : '' }}>{{ $label[$a] ?? $a }}</option>@endforeach</select></div>
        <div><label class="text-xs font-medium text-slate-500">Dari</label><input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full mt-1 rounded-xl border-slate-200 text-sm"></div>
        <div><label class="text-xs font-medium text-slate-500">Sampai</label><input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full mt-1 rounded-xl border-slate-200 text-sm"></div>
        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-xl text-sm font-medium">Filter</button>
        @if(request()->hasAny(['user_id', 'action', 'start_date', 'end_date']))
        <a href="{{ route('activity-logs.index') }}" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-xl text-sm font-medium hover:bg-slate-200">Reset</a>
        @endif
    </form>
</div>

<!-- List -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="md:hidden divide-y divide-slate-100">
        @forelse($logs as $log)
        <div class="p-4">
            <div class="flex items-start justify-between gap-3 mb-2">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-slate-700 truncate">{{ $log->user?->name ?? 'Sistem / Tidak dikenal' }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">{{ $log->created_at->format('d/m/Y H:i:s') }}</p>
                </div>
                <span class="shrink-0 px-2 py-0.5 rounded-full text-xs font-semibold {{ $badge[$log->action] ?? 'bg-slate-100 text-slate-700' }}">{{ $label[$log->action] ?? $log->action }}</span>
            </div>
            <p class="text-sm text-slate-600">{{ $log->description }}</p>
            <p class="text-xs text-slate-400 mt-1">IP: {{ $log->ip_address ?? '-' }} @if($log->role)<span class="capitalize">· {{ $log->role }}</span>@endif</p>
        </div>
        @empty
        <div class="py-8 text-center text-slate-400">Belum ada aktivitas tercatat.</div>
        @endforelse
    </div>

    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100"><tr>
                <th class="text-left py-3 px-4 font-semibold text-slate-600">Waktu</th>
                <th class="text-left py-3 px-4 font-semibold text-slate-600">Pengguna</th>
                <th class="text-left py-3 px-4 font-semibold text-slate-600">Aksi</th>
                <th class="text-left py-3 px-4 font-semibold text-slate-600">Deskripsi</th>
                <th class="text-left py-3 px-4 font-semibold text-slate-600">IP</th>
            </tr></thead>
            <tbody>
                @forelse($logs as $log)
                <tr class="border-b border-slate-50 hover:bg-slate-50/50">
                    <td class="py-3 px-4 text-slate-500 whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                    <td class="py-3 px-4">
                        <p class="font-medium text-slate-700">{{ $log->user?->name ?? 'Sistem / Tidak dikenal' }}</p>
                        @if($log->role)<p class="text-xs text-slate-400 capitalize">{{ $log->role }}</p>@endif
                    </td>
                    <td class="py-3 px-4"><span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $badge[$log->action] ?? 'bg-slate-100 text-slate-700' }}">{{ $label[$log->action] ?? $log->action }}</span></td>
                    <td class="py-3 px-4 text-slate-600">{{ $log->description }}</td>
                    <td class="py-3 px-4 text-slate-400 font-mono text-xs">{{ $log->ip_address ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="py-8 text-center text-slate-400">Belum ada aktivitas tercatat.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 border-t border-slate-100">{{ $logs->links() }}</div>
</div>
</x-app-layout>