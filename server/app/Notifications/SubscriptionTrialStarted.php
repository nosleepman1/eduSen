<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionTrialStarted extends Notification implements ShouldQueue
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
            ->subject("Votre période d'essai a commencé ! - Galsen School")
            ->view('emails.subscription_status', [
                'subscription' => $this->subscription,
                'title' => "Début de votre période d'essai gratuit",
                'introMessage' => "Nous avons le plaisir de vous informer que votre période d'essai gratuit de 30 jours pour l'établissement <strong>{$this->subscription->school->name}</strong> a commencé. Vous pouvez dès à présent utiliser toutes les fonctionnalités de la plateforme.",
                'actionUrl' => $frontendUrl,
                'actionText' => "Accéder à mon espace scolaire"
            ]);
    }
}
