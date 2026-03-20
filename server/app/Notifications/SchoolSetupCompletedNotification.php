<?php

namespace App\Notifications;

use App\Models\School;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SchoolSetupCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public School $school)
    {
        $this->school->loadMissing(['details', 'addresses', 'phones']);
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Configuration de votre école réussie - {$this->school->name}")
            ->view('emails.school_setup_completed', [
                'school' => $this->school,
                'owner' => $notifiable,
            ]);
    }
}
