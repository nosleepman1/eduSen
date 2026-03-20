<?php

namespace App\Listeners;

use App\Events\SchoolRegistrationApproved;
use App\Notifications\RegistrationApprovedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendSchoolRegistrationApprovalNotificationToOwner implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(SchoolRegistrationApproved $event): void
    {
        $event->owner->notify(new RegistrationApprovedNotification($event->school, $event->token));
    }
}
