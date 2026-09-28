<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    public function log(string $action, string $description, ?User $user = null): ActivityLog
    {
        $user ??= auth()->user();

        if (! config('activity-log.enabled')) {
            return new ActivityLog;
        }

        $log = ActivityLog::create([
            'user_id' => $user?->id,
            'role' => $user?->roles->first()?->name,
            'action' => $action,
            'description' => $description,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);

        $this->prune();

        return $log;
    }

    /**
     * Hapus record tertua apabila jumlah record melebihi batas maksimal.
     */
    public function prune(?int $maxLines = null): int
    {
        $maxLines ??= (int) config('activity-log.max_lines', 500);

        $total = ActivityLog::count();

        if ($total <= $maxLines) {
            return 0;
        }

        $threshold = ActivityLog::orderByDesc('id')
            ->skip($maxLines)
            ->value('id');

        if ($threshold === null) {
            return 0;
        }

        return ActivityLog::where('id', '<=', $threshold)->delete();
    }
}
