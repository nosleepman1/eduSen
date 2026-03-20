<?php

namespace App\Console\Commands;

use App\Services\Api\V1\Billing\SubscriptionService;
use Illuminate\Console\Command;

class CheckSubscriptionExpiryCommand extends Command
{
    protected $signature = 'subscriptions:check-expiry';

    protected $description = 'Vérifier la validité des abonnements et gérer les expirations et les périodes de grâce.';

    public function handle(SubscriptionService $service): int
    {
        $this->info("Vérification des abonnements en cours...");
        $service->checkAndProcessExpirations();
        $this->info("Vérification terminée.");

        return Command::SUCCESS;
    }
}
