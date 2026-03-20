<?php

namespace App\Listeners;

use App\Events\SchoolRegistrationRequested;
use App\Enums\RoleEnum;
use App\Models\User;
use App\Models\UserRole;
use App\Notifications\RegistrationRequestReceivedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendSchoolRegistrationRequestNotificationToSuperAdmins implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(SchoolRegistrationRequested $event): void
    {
        // Find all SuperAdmins via user_roles (no more users.role column)
        $superAdmins = User::whereHas('roles', function ($query) {
            $query->whereNull('school_id')
                ->where('role', RoleEnum::SuperAdmin)
                ->where('is_active', true);
        })->get();

        Notification::send($superAdmins, new RegistrationRequestReceivedNotification($event->registrationRequest));
    }
}
