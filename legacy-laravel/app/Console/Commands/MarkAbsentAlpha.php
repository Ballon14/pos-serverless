<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarkAbsentAlpha extends Command
{
    protected $signature = 'attendance:mark-alpha {--date= : Tanggal yang ingin dicek (YYYY-MM-DD), default: kemarin}';

    protected $description = 'Tandai karyawan yang tidak clock-in dan tidak izin/sakit/cuti sebagai Alpha';

    public function handle(): int
    {
        if (! config('app.attendance_enabled')) {
            $this->info('Fitur absensi dinonaktifkan (ATTENDANCE_ENABLED=false). Tidak ada yang diproses.');

            return self::SUCCESS;
        }

        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))->toDateString()
            : Carbon::yesterday()->toDateString();

        $this->info("Memproses absensi alpha untuk tanggal: {$date}");

        // Ambil semua karyawan aktif (exclude admin)
        $employees = Employee::where('is_active', true)
            ->whereHas('user', function ($q) {
                $q->whereHas('roles', function ($r) {
                    $r->where('name', '!=', 'admin');
                });
            })
            ->get();

        $alphaCount = 0;

        foreach ($employees as $employee) {
            // Cek apakah sudah ada data absensi hari itu
            $hasAttendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('tanggal', $date)
                ->exists();

            if ($hasAttendance) {
                continue;
            }

            // Cek apakah ada izin/cuti/sakit yang disetujui pada tanggal itu
            $hasLeave = LeaveRequest::where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->whereDate('tanggal_mulai', '<=', $date)
                ->whereDate('tanggal_selesai', '>=', $date)
                ->exists();

            if ($hasLeave) {
                continue;
            }

            // Buat data absensi dengan status alpha
            Attendance::create([
                'employee_id' => $employee->id,
                'tanggal' => $date,
                'status' => 'alpha',
                'keterangan' => 'Otomatis ditandai alpha oleh sistem',
            ]);

            $alphaCount++;
            $this->line("  ✗ {$employee->nama} → Alpha");
        }

        $this->info("Selesai. {$alphaCount} karyawan ditandai alpha.");

        return self::SUCCESS;
    }
}
