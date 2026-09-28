<x-app-layout>
@section('title', 'Laporan Absensi')
<x-slot name="header">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-2xl font-bold text-slate-800">Laporan Absensi Karyawan</h2>
        <form method="GET" action="{{ route('reports.attendance') }}" target="_blank">
            <input type="hidden" name="start_date" value="{{ $startDate }}">
            <input type="hidden" name="end_date" value="{{ $endDate }}">
            <input type="hidden" name="employee_id" value="{{ $employeeId }}">
            <input type="hidden" name="export" value="pdf">
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl text-sm font-semibold shadow-lg shadow-red-500/30 hover:shadow-red-500/50 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export PDF
            </button>
        </form>
    </div>
</x-slot>

<!-- Filter -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="text-xs font-medium text-slate-500">Dari</label>
            <input type="date" name="start_date" value="{{ $startDate }}" class="w-full mt-1 rounded-xl border-slate-200 text-sm">
        </div>
        <div>
            <label class="text-xs font-medium text-slate-500">Sampai</label>
            <input type="date" name="end_date" value="{{ $endDate }}" class="w-full mt-1 rounded-xl border-slate-200 text-sm">
        </div>
        <div class="w-64">
            <label class="text-xs font-medium text-slate-500">Karyawan</label>
            <select name="employee_id" class="w-full mt-1 rounded-xl border-slate-200 text-sm">
                <option value="">Semua Karyawan</option>
                @foreach($employees as $emp)
                <option value="{{ $emp->id }}" {{ $employeeId == $emp->id ? 'selected' : '' }}>{{ $emp->nama }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-xl text-sm font-medium">Tampilkan</button>
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <!-- Mobile: card list -->
    <div class="md:hidden divide-y divide-slate-100">
        @forelse($data['rows'] as $row)
        <div class="p-4">
            <div class="flex items-start justify-between gap-3 mb-3">
                <div class="min-w-0">
                    <p class="font-medium text-slate-700 truncate">{{ $row['employee_name'] }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">{{ $row['employee_jabatan'] }}</p>
                </div>
                <div class="text-right shrink-0">
                    <p class="text-xs font-semibold {{ $row['attendance_percentage'] >= 80 ? 'text-emerald-700' : ($row['attendance_percentage'] >= 60 ? 'text-amber-700' : 'text-red-700') }}">{{ $row['attendance_percentage'] }}%</p>
                    <div class="w-16 h-1.5 bg-slate-100 rounded-full overflow-hidden mt-1 ml-auto">
                        <div class="h-full {{ $row['attendance_percentage'] >= 80 ? 'bg-emerald-500' : ($row['attendance_percentage'] >= 60 ? 'bg-amber-500' : 'bg-red-500') }}" style="width: {{ $row['attendance_percentage'] }}%"></div>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs">
                <span class="text-slate-500">Aktif: <span class="font-semibold text-slate-700">{{ $row['total_days'] }}</span></span>
                <span class="font-bold text-emerald-600">Hadir {{ $row['hadir'] }}</span>
                <span class="text-amber-500">Sakit {{ $row['sakit'] }}</span>
                <span class="text-indigo-500">Izin {{ $row['izin'] }}</span>
                <span class="text-purple-500">Cuti {{ $row['cuti'] }}</span>
                <span class="font-bold text-red-500">Alpha {{ $row['alpha'] }}</span>
            </div>
        </div>
        @empty
        <div class="py-8 text-center text-slate-400">Tidak ada data absensi.</div>
        @endforelse
    </div>

    <!-- Desktop: table -->
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="text-left py-3 px-4 font-semibold text-slate-600">Karyawan</th>
                    <th class="text-left py-3 px-4 font-semibold text-slate-600">Jabatan</th>
                    <th class="text-center py-3 px-4 font-semibold text-slate-600">Total Hari Aktif</th>
                    <th class="text-center py-3 px-4 font-semibold text-slate-600">Hadir</th>
                    <th class="text-center py-3 px-4 font-semibold text-slate-600">Sakit</th>
                    <th class="text-center py-3 px-4 font-semibold text-slate-600">Izin</th>
                    <th class="text-center py-3 px-4 font-semibold text-slate-600">Cuti</th>
                    <th class="text-center py-3 px-4 font-semibold text-slate-600">Alpha</th>
                    <th class="text-center py-3 px-4 font-semibold text-slate-600">Presentase</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data['rows'] as $row)
                <tr class="border-b border-slate-50 hover:bg-slate-50/50">
                    <td class="py-3 px-4 font-medium text-slate-700">{{ $row['employee_name'] }}</td>
                    <td class="py-3 px-4 text-slate-500">{{ $row['employee_jabatan'] }}</td>
                    <td class="py-3 px-4 text-center font-semibold text-slate-700">{{ $row['total_days'] }}</td>
                    <td class="py-3 px-4 text-center font-bold text-emerald-600">{{ $row['hadir'] }}</td>
                    <td class="py-3 px-4 text-center text-amber-500">{{ $row['sakit'] }}</td>
                    <td class="py-3 px-4 text-center text-indigo-500">{{ $row['izin'] }}</td>
                    <td class="py-3 px-4 text-center text-purple-500">{{ $row['cuti'] }}</td>
                    <td class="py-3 px-4 text-center font-bold text-red-500">{{ $row['alpha'] }}</td>
                    <td class="py-3 px-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <div class="w-16 h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full {{ $row['attendance_percentage'] >= 80 ? 'bg-emerald-500' : ($row['attendance_percentage'] >= 60 ? 'bg-amber-500' : 'bg-red-500') }}" style="width: {{ $row['attendance_percentage'] }}%"></div>
                            </div>
                            <span class="text-xs font-semibold {{ $row['attendance_percentage'] >= 80 ? 'text-emerald-700' : ($row['attendance_percentage'] >= 60 ? 'text-amber-700' : 'text-red-700') }}">{{ $row['attendance_percentage'] }}%</span>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="py-8 text-center text-slate-400">Tidak ada data absensi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 border-t border-slate-100">{{ $data['employees']->appends(request()->query())->links() }}</div>
</div>
</x-app-layout>
