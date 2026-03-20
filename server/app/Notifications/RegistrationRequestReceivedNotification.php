<?php

namespace App\Notifications;

use App\Models\SchoolRegistrationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationRequestReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(private SchoolRegistrationRequest $registrationRequest) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nouvelle demande d\'inscription - EDU SEN')
            ->view('emails.registration_request', [
                'request' => $this->registrationRequest,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'registration_request_id' => $this->registrationRequest->id,
            'school_name' => $this->registrationRequest->school_name,
            'owner_email' => $this->registrationRequest->email,
        ];
    }
}
