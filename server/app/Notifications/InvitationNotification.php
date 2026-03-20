<?php

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Invitation $invitation
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Typically, frontend URL
        $url = config('app.frontend_url') . '/invitation/accept?token=' . $this->invitation->token;

        return (new MailMessage)
            ->subject('Invitation à rejoindre ' . $this->invitation->school->name)
            ->greeting('Bonjour ' . $this->invitation->firstname . ',')
            ->line('Vous avez été invité(e) par ' . $this->invitation->creator->full_name . ' à rejoindre l\'établissement ' . $this->invitation->school->name . ' en tant que ' . $this->invitation->role->value . '.')
            ->action('Accepter l\'invitation', $url)
            ->line('Cette invitation expire le ' . $this->invitation->expires_at->format('d/m/Y à H:i') . '.')
            ->line('Si vous ne connaissez pas cet établissement, vous pouvez ignorer cet email.');
    }
}
