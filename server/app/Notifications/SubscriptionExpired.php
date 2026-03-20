<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionExpired extends Notification implements ShouldQueue
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
            ->subject("Votre abonnement a expiré - Galsen School")
            ->view('emails.subscription_status', [
                'subscription' => $this->subscription,
                'title' => "Abonnement expiré",
                'introMessage' => "Votre abonnement pour l'établissement <strong>{$this->subscription->school->name}</strong> a expiré. Vous êtes actuellement en **période de grâce de 7 jours** (accès limité en lecture seule). Veuillez renouveler votre abonnement afin de réactiver toutes les fonctionnalités.",
                'actionUrl' => $plansUrl,
                'actionText' => "Renouveler mon abonnement"
            ]);
    }
}
