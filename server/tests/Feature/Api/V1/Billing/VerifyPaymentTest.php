<?php

use App\Models\BillingCycle;
use App\Models\School;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Services\Api\V1\Billing\PayDunyaService;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
    // Dummy mode reports every payment as completed. That is exactly the
    // dangerous setting, so the guards below are exercised under it.
    config()->set('services.paydunya.use_dummy', true);
});

function pendingInvoiceFor(School $school, float $amount = 15000.00): SubscriptionInvoice
{
    $plan = SubscriptionPlan::create([
        'name' => 'Premium '.uniqid(),
        'type' => 'MONTHLY',
        'price' => $amount,
        'currency' => 'FCFA',
        'duration' => 1,
        'max_students' => 500,
        'max_teachers' => 50,
        'features' => [],
        'is_active' => true,
    ]);

    $cycle = BillingCycle::create([
        'code' => 'monthly-'.uniqid(),
        'name' => 'Mensuel',
        'duration_days' => 30,
        'discount_percentage' => 0.00,
        'active' => true,
    ]);

    return SubscriptionInvoice::create([
        'school_id' => $school->id,
        'subscription_plan_id' => $plan->id,
        'billing_cycle_id' => $cycle->id,
        'amount' => $amount,
        'currency' => 'FCFA',
        'due_date' => now()->addDays(7),
    ]);
}

it("refuses to confirm another school's invoice", function () {
    $owner = ownerWithActiveSubscription();
    $foreignInvoice = pendingInvoiceFor(School::factory()->create());

    $this->withToken($owner->test_token)
        ->postJson('/api/v1/subscription/verify-payment?token=dummy_token_'.$foreignInvoice->uuid)
        ->assertStatus(403);

    expect($foreignInvoice->fresh()->status)->toBe('PENDING');
    expect(SubscriptionPayment::count())->toBe(0);
});

it('confirms an invoice belonging to the caller', function () {
    $owner = ownerWithActiveSubscription();
    $invoice = pendingInvoiceFor($owner->test_school);

    $this->withToken($owner->test_token)
        ->postJson('/api/v1/subscription/verify-payment?token=dummy_token_'.$invoice->uuid)
        ->assertOk()
        ->assertJsonPath('data.outcome', 'PAID');

    $invoice->refresh();
    expect($invoice->status)->toBe('PAID');
    expect($invoice->subscription_id)->not->toBeNull();
    expect(SubscriptionPayment::sole()->status)->toBe('SUCCESS');
});

it('does not re-subscribe when the caller replays the confirmation', function () {
    $owner = ownerWithActiveSubscription();
    $invoice = pendingInvoiceFor($owner->test_school);
    $url = '/api/v1/subscription/verify-payment?token=dummy_token_'.$invoice->uuid;

    $this->withToken($owner->test_token)->postJson($url)->assertOk()
        ->assertJsonPath('data.outcome', 'PAID');
    $this->withToken($owner->test_token)->postJson($url)->assertOk()
        ->assertJsonPath('data.outcome', 'ALREADY_PROCESSED');

    expect(SubscriptionPayment::count())->toBe(1);
    // One from ownerWithActiveSubscription(), one from the confirmed invoice.
    expect(Subscription::where('school_id', $owner->test_school->id)->where('status', 'ACTIVE')->count())->toBe(1);
});

it('never enables dummy mode outside local and testing', function () {
    config()->set('services.paydunya.use_dummy', true);

    expect(app(PayDunyaService::class)->useDummyMode())->toBeTrue();

    app()->detectEnvironment(fn () => 'production');

    expect(app(PayDunyaService::class)->useDummyMode())->toBeFalse();
});
