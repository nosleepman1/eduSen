<?php

namespace App\Notifications;

use App\Models\School;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(private School $school, private string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5174');
        $url = rtrim($frontendUrl, '/').'/set-password?token='.$this->token.'&email='.urlencode($notifiable->email);

        return (new MailMessage)
            ->subject('Votre école est approuvée -  EDU SEN')
            ->view('emails.registration_approved', [
                'school' => $this->school,
                'owner' => $notifiable,
                'url' => $url,
            ]);
    }
}
