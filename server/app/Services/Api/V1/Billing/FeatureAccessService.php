<?php

namespace App\Services\Api\V1\Billing;

use App\Models\School;
use App\Models\SubscriptionStatusEnum;

class FeatureAccessService
{
    public function hasFeature(School $school, string $featureCode): bool
    {
        $subscription = $school->subscriptions()
            ->whereIn('status', [
                SubscriptionStatusEnum::Active,
                SubscriptionStatusEnum::Trial,
                SubscriptionStatusEnum::GracePeriod
            ])
            ->where(function ($query) {
                $query->where('ends_at', '>=', now())
                    ->orWhere('grace_ends_at', '>=', now());
            })
            ->first();

        if (!$subscription) {
            return false;
        }

        $plan = $subscription->plan;
        if (!$plan) {
            return false;
        }

        $planFeature = $plan->planFeatures()
            ->whereHas('feature', function ($query) use ($featureCode) {
                $query->where('code', $featureCode);
            })
            ->first();

        return $planFeature ? (bool) $planFeature->active : false;
    }

    public function getFeatureLimit(School $school, string $featureCode): ?int
    {
        $subscription = $school->subscriptions()
            ->whereIn('status', [
                SubscriptionStatusEnum::Active,
                SubscriptionStatusEnum::Trial,
                SubscriptionStatusEnum::GracePeriod
            ])
            ->where(function ($query) {
                $query->where('ends_at', '>=', now())
                    ->orWhere('grace_ends_at', '>=', now());
            })
            ->first();

        if (!$subscription) {
            return 0;
        }

        $plan = $subscription->plan;
        if (!$plan) {
            return 0;
        }

        $planFeature = $plan->planFeatures()
            ->whereHas('feature', function ($query) use ($featureCode) {
                $query->where('code', $featureCode);
            })
            ->first();

        return $planFeature ? $planFeature->limit_value : null;
    }
}
