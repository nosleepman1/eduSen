<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionActivated extends Notification implements ShouldQueue
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

        return (new MailMessage)
            ->subject("Votre abonnement est activé ! - Galsen School")
            ->view('emails.subscription_status', [
                'subscription' => $this->subscription,
                'title' => "Confirmation d'activation de l'abonnement",
                'introMessage' => "Félicitations ! Votre abonnement pour l'établissement <strong>{$this->subscription->school->name}</strong> a été activé avec succès. Merci de votre confiance !",
                'actionUrl' => $frontendUrl,
                'actionText' => "Accéder au tableau de bord"
            ]);
    }
}
