<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionTrialExpiringSoon extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Subscription $subscription, public int $daysBefore)
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
            ->subject("Votre période d'essai expire dans {$this->daysBefore} jours ! - Galsen School")
            ->view('emails.subscription_status', [
                'subscription' => $this->subscription,
                'title' => "Expiration imminente de votre période d'essai",
                'introMessage' => "Votre période d'essai pour l'établissement <strong>{$this->subscription->school->name}</strong> arrive à expiration dans <strong>{$this->daysBefore} jour(s)</strong>. Pour éviter toute interruption de service, veuillez choisir un plan d'abonnement payant dès maintenant.",
                'actionUrl' => $plansUrl,
                'actionText' => "S'abonner maintenant"
            ]);
    }
}
