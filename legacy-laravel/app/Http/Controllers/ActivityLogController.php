<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Carbon\Carbon;

class ActivityLogController extends Controller
{
    public function index()
    {
        $query = ActivityLog::with('user')->latest('id');

        if ($userId = request('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($action = request('action')) {
            $query->where('action', $action);
        }

        if ($startDate = request('start_date')) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate = request('end_date')) {
            $query->where('created_at', '<', Carbon::parse($endDate)->addDay());
        }

        $logs = $query->paginate(15)->withQueryString();

        $users = User::orderBy('name')->get();
        $actions = ActivityLog::select('action')->distinct()->orderBy('action')->pluck('action');

        $stats = [
            'total' => ActivityLog::count(),
            'today' => ActivityLog::where('created_at', '>=', today())->where('created_at', '<', today()->addDay())->count(),
            'failed_login' => ActivityLog::where('action', 'auth.login_failed')->count(),
        ];

        return view('activity-logs.index', compact('logs', 'users', 'actions', 'stats'));
    }
}
