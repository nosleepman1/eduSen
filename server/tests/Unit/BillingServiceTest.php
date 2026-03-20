<?php

use App\Models\BillingCycle;
use App\Models\SubscriptionPlan;
use App\Services\Api\V1\Billing\BillingService;

it('calculates subscription prices correctly with billing cycle discounts', function () {
    $plan = new SubscriptionPlan([
        'price' => 5000.00,
    ]);

    $cycleMonthly = new BillingCycle([
        'duration_days' => 30,
        'discount_percentage' => 0.00,
    ]);

    $cycleAnnual = new BillingCycle([
        'duration_days' => 360,
        'discount_percentage' => 15.00,
    ]);

    $service = new BillingService();

    // 1 Month, 0% discount
    $resultMonthly = $service->calculatePrice($plan, $cycleMonthly);
    expect($resultMonthly['base_price'])->toEqual(5000.00)
        ->and($resultMonthly['discount_amount'])->toEqual(0.00)
        ->and($resultMonthly['final_price'])->toEqual(5000.00);

    // 12 Months, 15% discount
    $resultAnnual = $service->calculatePrice($plan, $cycleAnnual);
    $expectedBase = 5000.00 * 12; // 60000.00
    $expectedDiscount = $expectedBase * 0.15; // 9000.00
    $expectedFinal = $expectedBase - $expectedDiscount; // 51000.00

    expect($resultAnnual['base_price'])->toEqual($expectedBase)
        ->and($resultAnnual['discount_amount'])->toEqual($expectedDiscount)
        ->and($resultAnnual['final_price'])->toEqual($expectedFinal);
});
