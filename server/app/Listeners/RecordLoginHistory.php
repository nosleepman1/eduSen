<?php

namespace App\Listeners;

use App\Models\LoginHistory;
use App\Support\TenantHelper;
use Illuminate\Auth\Events\Login;

class RecordLoginHistory
{
    public function handle(Login $event): void
    {
        // Record login history
        // Note: At the exact moment of 'Login', the user hasn't selected a school yet
        // so school_id will be null initially for multi-school users.
        LoginHistory::create([
            'user_id' => $event->user->id,
            'school_id' => TenantHelper::currentSchoolId(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'device' => $this->detectDevice(request()->userAgent()),
            'logged_in_at' => now(),
        ]);
    }

    private function detectDevice(?string $userAgent): string
    {
        if (! $userAgent) return 'Unknown';

        if (preg_match('/mobile/i', $userAgent)) return 'Mobile';
        if (preg_match('/tablet/i', $userAgent)) return 'Tablet';

        return 'Desktop';
    }
}
