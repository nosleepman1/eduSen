<?php

namespace App\Listeners;

use App\Events\InvitationCreated;
use App\Notifications\InvitationNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendInvitationEmail implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(InvitationCreated $event): void
    {
        // Using an anonymous notifiable since the user doesn't exist yet
        Notification::route('mail', $event->invitation->email)
            ->notify(new InvitationNotification($event->invitation));
    }
}
