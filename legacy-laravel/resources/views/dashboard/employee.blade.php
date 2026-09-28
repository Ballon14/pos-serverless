<x-app-layout>
@section('title', 'Dashboard Karyawan')

<x-slot name="header">
    <div>
        <h2 class="text-xl sm:text-2xl font-bold text-slate-800">Dashboard</h2>
        <p class="text-sm text-slate-500 mt-0.5">Selamat {{ now()->hour < 12 ? 'pagi' : (now()->hour < 15 ? 'siang' : (now()->hour < 18 ? 'sore' : 'malam')) }}, {{ auth()->user()->name }} 👋</p>
    </div>
</x-slot>

<div class="max-w-2xl mx-auto">
    @if(config('app.attendance_enabled'))
    <!-- Riwayat Absensi Terakhir -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50">
            <h3 class="text-base font-bold text-slate-800">Riwayat Absensi Terakhir</h3>
            <p class="text-xs text-slate-500 mt-0.5">7 hari terakhir</p>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse($recentAttendances as $att)
            <div class="flex items-center justify-between p-4">
                <div>
                    <p class="text-sm font-medium text-slate-700">{{ $att->tanggal->translatedFormat('l, d M Y') }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">
                        {{ $att->clock_in ? \Carbon\Carbon::parse($att->clock_in)->format('H:i') : '-' }} -
                        {{ $att->clock_out ? \Carbon\Carbon::parse($att->clock_out)->format('H:i') : '-' }}
                    </p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-semibold
                    {{ $att->status === 'hadir' ? 'bg-emerald-100 text-emerald-700' :
                       ($att->status === 'alpha' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                    {{ $att->status_label }}
                </span>
            </div>
            @empty
            <div class="p-8 text-center">
                <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <p class="text-sm text-slate-500">Belum ada riwayat absensi</p>
            </div>
            @endforelse
        </div>
    </div>
    @else
    <!-- Attendance disabled -->
    <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl p-8 text-center text-white shadow-lg shadow-indigo-500/20">
        <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        </div>
        <h3 class="text-lg font-bold">Selamat datang!</h3>
        <p class="text-indigo-100 text-sm mt-1">{{ now()->translatedFormat('l, d F Y') }}</p>
    </div>
    @endif
</div>
</x-app-layout>
