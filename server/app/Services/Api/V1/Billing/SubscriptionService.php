<?php

namespace App\Services\Api\V1\Billing;

use App\Enums\SubscriptionStatusEnum;
use App\Models\BillingCycle;
use App\Models\School;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Notifications\SubscriptionActivated;
use App\Notifications\SubscriptionCancelled;
use App\Notifications\SubscriptionExpired;
use App\Notifications\SubscriptionExpiringSoon;
use App\Notifications\SubscriptionRenewed;
use App\Notifications\SubscriptionTrialExpired;
use App\Notifications\SubscriptionTrialExpiringSoon;
use App\Notifications\SubscriptionTrialStarted;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function __construct(
        private BillingService $billingService
    ) {}

    public function assignTrial(School $school): Subscription
    {
        return DB::transaction(function () use ($school) {
            // Cancel other active/trial subscriptions
            $school->subscriptions()->whereIn('status', [
                SubscriptionStatusEnum::Active,
                SubscriptionStatusEnum::Trial,
                SubscriptionStatusEnum::GracePeriod
            ])->update(['status' => SubscriptionStatusEnum::Cancelled, 'auto_renew' => false]);

            $plan = SubscriptionPlan::query()->where('is_active', true)->oldest()->first()
                ?? SubscriptionPlan::query()->create([
                    'name' => 'Basic',
                    'price' => 5000,
                    'currency' => 'FCFA',
                    'type' => 'MONTHLY',
                    'duration' => 1,
                    'max_students' => 200,
                    'max_teachers' => 20,
                    'features' => ['auth', 'setup'],
                    'is_active' => true,
                ]);

            $subscription = $school->subscriptions()->create([
                'subscription_plan_id' => $plan->id,
                'billing_cycle_id' => null,
                'status' => SubscriptionStatusEnum::Trial,
                'starts_at' => now(),
                'ends_at' => now()->addDays(30),
                'grace_ends_at' => null,
                'auto_renew' => false,
                'plan_name_snapshot' => $plan->name,
                'plan_price_snapshot' => 0.00, // Trial is free
                'billing_cycle_snapshot' => 'Trial 30 Days',
                'discount_snapshot' => 0.00,
            ]);

            if ($school->owner) {
                $school->owner->notify(new SubscriptionTrialStarted($subscription));
            }

            return $subscription;
        });
    }

    public function subscribe(School $school, SubscriptionPlan $plan, BillingCycle $cycle): Subscription
    {
        return DB::transaction(function () use ($school, $plan, $cycle) {
            // Deactivate previous active/trial/grace subscriptions
            $school->subscriptions()->whereIn('status', [
                SubscriptionStatusEnum::Active,
                SubscriptionStatusEnum::Trial,
                SubscriptionStatusEnum::GracePeriod,
                SubscriptionStatusEnum::Suspended
            ])->update(['status' => SubscriptionStatusEnum::Expired, 'auto_renew' => false]);

            $pricing = $this->billingService->calculatePrice($plan, $cycle);

            $subscription = $school->subscriptions()->create([
                'subscription_plan_id' => $plan->id,
                'billing_cycle_id' => $cycle->id,
                'status' => SubscriptionStatusEnum::Active,
                'starts_at' => now(),
                'ends_at' => now()->addDays($cycle->duration_days),
                'grace_ends_at' => null,
                'auto_renew' => true,
                'plan_name_snapshot' => $plan->name,
                'plan_price_snapshot' => $pricing['final_price'],
                'billing_cycle_snapshot' => $cycle->name,
                'discount_snapshot' => $pricing['discount_percentage'],
            ]);

            if ($school->owner) {
                $school->owner->notify(new SubscriptionActivated($subscription));
            }

            return $subscription;
        });
    }

    public function renew(Subscription $subscription): Subscription
    {
        return DB::transaction(function () use ($subscription) {
            if (!$subscription->billingCycle) {
                // Cannot auto-renew trial subscription without standard cycle
                throw new \Exception("Impossible de renouveler un abonnement d'essai sans cycle de facturation.");
            }

            $cycle = $subscription->billingCycle;
            $plan = $subscription->plan;

            $pricing = $this->billingService->calculatePrice($plan, $cycle);

            $startsAt = $subscription->ends_at->isFuture() ? $subscription->ends_at : now();
            $endsAt = (clone $startsAt)->addDays($cycle->duration_days);

            $newSubscription = $subscription->school->subscriptions()->create([
                'subscription_plan_id' => $plan->id,
                'billing_cycle_id' => $cycle->id,
                'status' => SubscriptionStatusEnum::Active,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'grace_ends_at' => null,
                'auto_renew' => true,
                'plan_name_snapshot' => $plan->name,
                'plan_price_snapshot' => $pricing['final_price'],
                'billing_cycle_snapshot' => $cycle->name,
                'discount_snapshot' => $pricing['discount_percentage'],
            ]);

            // Set old one as expired or replaced
            $subscription->update(['status' => SubscriptionStatusEnum::Expired, 'auto_renew' => false]);

            $owner = $newSubscription->school->owner;
            if ($owner) {
                $owner->notify(new SubscriptionRenewed($newSubscription));
            }

            return $newSubscription;
        });
    }

    public function cancel(Subscription $subscription, string $reason): Subscription
    {
        $subscription->update([
            'status' => SubscriptionStatusEnum::Cancelled,
            'auto_renew' => false,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        $owner = $subscription->school->owner;
        if ($owner) {
            $owner->notify(new SubscriptionCancelled($subscription));
        }

        return $subscription;
    }

    public function checkAndProcessExpirations(): void
    {
        // 1. Process trial subscriptions that expired today
        $expiredTrials = Subscription::query()
            ->where('status', SubscriptionStatusEnum::Trial)
            ->where('ends_at', '<', now())
            ->get();

        foreach ($expiredTrials as $sub) {
            $sub->update([
                'status' => SubscriptionStatusEnum::TrialExpired,
                'grace_ends_at' => $sub->ends_at->addDays(7),
            ]);

            $owner = $sub->school->owner;
            if ($owner) {
                $owner->notify(new SubscriptionTrialExpired($sub));
            }
        }

        // 2. Process active subscriptions that expired today
        $expiredActive = Subscription::query()
            ->where('status', SubscriptionStatusEnum::Active)
            ->where('ends_at', '<', now())
            ->get();

        foreach ($expiredActive as $sub) {
            // Check if auto_renew is active and we can simulate payment
            if ($sub->auto_renew && $sub->billingCycle) {
                try {
                    $this->renew($sub);
                    continue;
                } catch (\Exception $e) {
                    // Fail over to grace period
                }
            }

            $sub->update([
                'status' => SubscriptionStatusEnum::GracePeriod,
                'grace_ends_at' => $sub->ends_at->addDays(7),
            ]);

            $owner = $sub->school->owner;
            if ($owner) {
                $owner->notify(new SubscriptionExpired($sub));
            }
        }

        // 3. Process grace periods (TrialExpired or GracePeriod) that have fully ended
        $suspendedSubs = Subscription::query()
            ->whereIn('status', [SubscriptionStatusEnum::TrialExpired, SubscriptionStatusEnum::GracePeriod])
            ->where('grace_ends_at', '<', now())
            ->get();

        foreach ($suspendedSubs as $sub) {
            $sub->update([
                'status' => SubscriptionStatusEnum::Suspended,
            ]);
        }

        // 4. Send reminders for nearing expirations (Active or Trial ending in 7, 5, 3, 1 days)
        $this->sendExpiryReminders(7);
        $this->sendExpiryReminders(5);
        $this->sendExpiryReminders(3);
        $this->sendExpiryReminders(1);
    }

    private function sendExpiryReminders(int $daysBefore): void
    {
        $targetDateStart = now()->addDays($daysBefore)->startOfDay();
        $targetDateEnd = now()->addDays($daysBefore)->endOfDay();

        $subs = Subscription::query()
            ->whereIn('status', [SubscriptionStatusEnum::Active, SubscriptionStatusEnum::Trial])
            ->whereBetween('ends_at', [$targetDateStart, $targetDateEnd])
            ->get();

        foreach ($subs as $sub) {
            $owner = $sub->school->owner;
            if ($owner) {
                if ($sub->status === SubscriptionStatusEnum::Trial) {
                    $owner->notify(new SubscriptionTrialExpiringSoon($sub, $daysBefore));
                } else {
                    $owner->notify(new SubscriptionExpiringSoon($sub, $daysBefore));
                }
            }
        }
    }
}
