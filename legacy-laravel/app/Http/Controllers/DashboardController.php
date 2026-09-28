<?php

namespace App\Http\Controllers;

use App\Services\AttendanceService;
use App\Services\ReportService;

class DashboardController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    public function index()
    {
        $user = auth()->user();

        if ($user->hasRole(['admin'])) {
            $data = $this->reportService->getDashboardData();
            $data['attendance_summary'] = config('app.attendance_enabled')
                ? app(AttendanceService::class)->getTodaySummary()
                : ['total' => 0, 'hadir' => 0, 'berizin' => 0, 'tidak_hadir' => 0];

            return view('dashboard.admin', compact('data'));
        }

        if ($user->hasRole('kasir')) {
            $data = $this->reportService->getDashboardData();

            return view('dashboard.admin', compact('data'));
        }

        // Karyawan/staff
        $employee = $user->employee;
        $todayAttendance = null;
        $recentAttendances = collect();

        if ($employee && config('app.attendance_enabled')) {
            $todayAttendance = app(AttendanceService::class)->getTodayAttendance($employee);
            $recentAttendances = $employee->attendances()->latest('tanggal')->limit(7)->get();
        }

        return view('dashboard.employee', compact('todayAttendance', 'recentAttendances'));
    }
}
