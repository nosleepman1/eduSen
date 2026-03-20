<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionTrialExpired extends Notification implements ShouldQueue
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
            ->subject("Votre période d'essai a expiré - Galsen School")
            ->view('emails.subscription_status', [
                'subscription' => $this->subscription,
                'title' => "Période d'essai expirée",
                'introMessage' => "Votre période d'essai gratuit pour l'établissement <strong>{$this->subscription->school->name}</strong> a expiré. Vous êtes entré dans une <strong>période de grâce de 7 jours</strong> pendant laquelle votre accès est limité en <strong>lecture seule</strong>. Veuillez vous abonner pour réactiver toutes les fonctionnalités.",
                'actionUrl' => $plansUrl,
                'actionText' => "S'abonner et réactiver"
            ]);
    }
}
