<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionRenewed extends Notification implements ShouldQueue
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
            ->subject("Votre abonnement est renouvelé ! - Galsen School")
            ->view('emails.subscription_status', [
                'subscription' => $this->subscription,
                'title' => "Confirmation de renouvellement d'abonnement",
                'introMessage' => "Votre abonnement pour l'établissement <strong>{$this->subscription->school->name}</strong> a été renouvelé avec succès pour une nouvelle période. Merci de continuer l'aventure avec nous !",
                'actionUrl' => $frontendUrl,
                'actionText' => "Accéder à mon espace"
            ]);
    }
}
