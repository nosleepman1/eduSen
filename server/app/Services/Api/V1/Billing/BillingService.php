<?php

namespace App\Services\Api\V1\Billing;

use App\Models\BillingCycle;
use App\Models\SubscriptionPlan;

class BillingService
{
    public function calculatePrice(SubscriptionPlan $plan, BillingCycle $cycle): array
    {
        $months = $cycle->duration_days / 30;
        $basePrice = $plan->price * $months;
        $discountAmount = $basePrice * ($cycle->discount_percentage / 100);
        $finalPrice = max(0, $basePrice - $discountAmount);

        return [
            'base_price' => round($basePrice, 2),
            'discount_amount' => round($discountAmount, 2),
            'final_price' => round($finalPrice, 2),
            'discount_percentage' => $cycle->discount_percentage,
        ];
    }
}
