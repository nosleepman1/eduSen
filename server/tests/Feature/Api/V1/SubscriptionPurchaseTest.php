<?php

use App\Enums\RoleEnum;
use App\Enums\SubscriptionStatusEnum;
use App\Models\BillingCycle;
use App\Models\School;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Notifications\SubscriptionActivated;
use App\Notifications\SubscriptionCancelled;
use App\Notifications\SubscriptionExpired;
use App\Notifications\SubscriptionRenewed;
use App\Notifications\SubscriptionTrialExpired;
use App\Notifications\SubscriptionTrialStarted;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

// Define test routes at the top of the file
beforeEach(function () {
    Route::middleware(['auth:sanctum', 'isolate.school', 'subscription.active'])->group(function () {
        Route::get('/_test/subscription-protected', fn() => response()->json(['success' => true]));
        Route::post('/_test/subscription-protected', fn() => response()->json(['success' => true]));
    });
});

function setupOwnerAndSchool(): array
{
    $owner = User::factory()->create(['password_changed_at' => now()]);
    $school = School::factory()->create([
        'owner_id' => $owner->id,
        'setup_step' => 2,
        'is_school_setup' => true,
    ]);
    
    \App\Models\Personnel::create([
        'user_id' => $owner->id,
        'school_id' => $school->id,
        'status' => \App\Enums\PersonnelStatusEnum::Active,
        'registration_number' => 'EMP-001'
    ]);
    
    \App\Models\UserRole::create([
        'user_id' => $owner->id,
        'school_id' => $school->id,
        'role' => RoleEnum::Owner,
        'is_active' => true,
        'assigned_at' => now()
    ]);
    
    $token = $owner->createToken('api', ['school_id' => $school->id])->plainTextToken;
    $owner->test_token = $token;

    $plan = SubscriptionPlan::query()->create([
        'name' => 'Premium',
        'type' => 'MONTHLY',
        'price' => 15000.00,
        'currency' => 'FCFA',
        'duration' => 1,
        'max_students' => 1000,
        'max_teachers' => 100,
        'features' => [],
        'is_active' => true,
    ]);

    $cycle = BillingCycle::query()->create([
        'code' => 'monthly',
        'name' => 'Mensuel',
        'duration_days' => 30,
        'discount_percentage' => 0.00,
        'active' => true,
    ]);

    app(\App\Services\Api\V1\Billing\SubscriptionService::class)->assignTrial($school);

    return [$owner, $school, $plan, $cycle];
}

it('assigns a trial subscription upon school onboarding', function () {
    Notification::fake();
    [$owner, $school, $plan, $cycle] = setupOwnerAndSchool();

    // Verify trial was assigned
    $subscription = $school->subscriptions()->first();
    expect($subscription)->not->toBeNull()
        ->and($subscription->status)->toEqual(SubscriptionStatusEnum::Trial)
        ->and($subscription->ends_at->isFuture())->toBeTrue();

    Notification::assertSentTo($owner, SubscriptionTrialStarted::class);
});

it('lists available plans and billing cycles', function () {
    [$owner, $school, $plan, $cycle] = setupOwnerAndSchool();

    $this->getJson('/api/v1/subscription/plans')
        ->assertOk()
        ->assertJsonFragment(['name' => 'Premium']);

    $this->getJson('/api/v1/subscription/billing-cycles')
        ->assertOk()
        ->assertJsonFragment(['code' => 'monthly']);
});

it('allows purchasing a paid subscription', function () {
    Notification::fake();
    [$owner, $school, $plan, $cycle] = setupOwnerAndSchool();

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)->postJson('/api/v1/subscription/subscribe', [
        'subscription_plan_id' => $plan->id,
        'billing_cycle_id' => $cycle->id,
    ])->assertOk();

    $subscription = $school->subscriptions()->orderBy('id', 'desc')->first();
    expect($subscription->status)->toEqual(SubscriptionStatusEnum::Active)
        ->and((float) $subscription->plan_price_snapshot)->toEqual(15000.00)
        ->and($subscription->billing_cycle_snapshot)->toEqual('Mensuel');

    Notification::assertSentTo($owner, SubscriptionActivated::class);
});

it('allows renewing a subscription', function () {
    Notification::fake();
    [$owner, $school, $plan, $cycle] = setupOwnerAndSchool();

    // Purchase active first
    $sub = $school->subscriptions()->create([
        'subscription_plan_id' => $plan->id,
        'billing_cycle_id' => $cycle->id,
        'status' => SubscriptionStatusEnum::Active,
        'starts_at' => now()->subDays(10),
        'ends_at' => now()->addDays(20),
        'auto_renew' => true,
    ]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)->postJson('/api/v1/subscription/renew', [
        'subscription_id' => $sub->id,
    ])->assertOk();

    $newSub = $school->subscriptions()->orderBy('id', 'desc')->first();
    expect($newSub->id)->not->toEqual($sub->id)
        ->and($newSub->status)->toEqual(SubscriptionStatusEnum::Active);

    Notification::assertSentTo($owner, SubscriptionRenewed::class);
});

it('allows cancelling a subscription', function () {
    Notification::fake();
    [$owner, $school, $plan, $cycle] = setupOwnerAndSchool();

    $school->subscriptions()->create([
        'subscription_plan_id' => $plan->id,
        'billing_cycle_id' => $cycle->id,
        'status' => SubscriptionStatusEnum::Active,
        'starts_at' => now(),
        'ends_at' => now()->addDays(60),
        'auto_renew' => true,
    ]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)->postJson('/api/v1/subscription/cancel', [
        'reason' => 'Too expensive for our budget',
    ])->assertOk();

    $sub = $school->subscriptions()->orderBy('id', 'desc')->first();
    expect($sub->status)->toEqual(SubscriptionStatusEnum::Cancelled)
        ->and($sub->auto_renew)->toBeFalse()
        ->and($sub->cancellation_reason)->toEqual('Too expensive for our budget');

    Notification::assertSentTo($owner, SubscriptionCancelled::class);
});

it('blocks access via middleware when subscription is inactive or suspended', function () {
    [$owner, $school, $plan, $cycle] = setupOwnerAndSchool();

    // Set trial subscription to suspended
    $school->subscriptions()->update(['status' => SubscriptionStatusEnum::Suspended]);

    // Use our protected test route
    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)->getJson('/_test/subscription-protected')
        ->assertForbidden();
});

it('allows read-only access (GET) with warnings but blocks modifications (POST) during grace period', function () {
    [$owner, $school, $plan, $cycle] = setupOwnerAndSchool();

    // Set trial subscription as expired but within grace period
    $school->subscriptions()->first()->update([
        'status' => SubscriptionStatusEnum::TrialExpired,
        'ends_at' => now()->subDays(2),
        'grace_ends_at' => now()->addDays(5),
    ]);

    // GET request should pass with warning header
    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)->getJson('/_test/subscription-protected');
    $response->assertOk();
    expect($response->headers->get('X-Subscription-Warning'))->not->toBeNull();

    // POST request should be blocked (read-only mode)
    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)->postJson('/_test/subscription-protected')
        ->assertForbidden();
});

it('runs check-expiry command to manage trials and paid plan expirations', function () {
    Notification::fake();
    [$owner, $school, $plan, $cycle] = setupOwnerAndSchool();

    // Expire the trial subscription ends_at
    $sub = $school->subscriptions()->first();
    $sub->update([
        'ends_at' => now()->subMinutes(1),
    ]);

    Artisan::call('subscriptions:check-expiry');

    // Subscription status should have transitioned to TrialExpired
    $sub->refresh();
    expect($sub->status)->toEqual(SubscriptionStatusEnum::TrialExpired)
        ->and($sub->grace_ends_at)->not->toBeNull();

    Notification::assertSentTo($owner, SubscriptionTrialExpired::class);
});
