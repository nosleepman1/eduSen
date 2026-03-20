<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionCancelled extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Subscription $subscription)
    {
        $this->subscription->loadMissing(['school', 'plan']);
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        $plansUrl = rtrim($frontendUrl, '/') . '/subscription/plans';

        return (new MailMessage)
            ->subject("Votre abonnement est annulé - Galsen School")
            ->view('emails.subscription_status', [
                'subscription' => $this->subscription,
                'title' => "Annulation de l'abonnement",
                'introMessage' => "Nous vous confirmons que votre abonnement pour l'établissement <strong>{$this->subscription->school->name}</strong> a bien été annulé. Il restera toutefois actif jusqu'à sa date d'échéance d'origine.",
                'actionUrl' => $plansUrl,
                'actionText' => "Réactiver mon abonnement"
            ]);
    }
}
