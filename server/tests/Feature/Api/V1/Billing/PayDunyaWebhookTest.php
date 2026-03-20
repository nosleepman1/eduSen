<?php

use App\Models\BillingCycle;
use App\Models\School;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\Notification;

const MASTER_KEY = 'test_master_key';

beforeEach(function () {
    Notification::fake();
    config()->set('services.paydunya.master_key', MASTER_KEY);
});

function paydunyaInvoice(float $amount = 15000.00): SubscriptionInvoice
{
    $school = School::factory()->create();

    $plan = SubscriptionPlan::create([
        'name' => 'Premium',
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

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function paydunyaPayload(SubscriptionInvoice $invoice, array $overrides = []): array
{
    return ['data' => array_merge([
        'hash' => hash('sha512', MASTER_KEY),
        'status' => 'completed',
        'receipt_id' => 'RCPT-'.$invoice->id,
        'invoice' => ['total_amount' => (float) $invoice->amount],
        'customer' => ['payment_method' => 'WAVE'],
        'custom_data' => ['invoice_id' => $invoice->id],
    ], $overrides)];
}

function postWebhook(array $payload)
{
    return test()->postJson('/api/v1/webhooks/paydunya/subscription', $payload);
}

// ── Signature ────────────────────────────────────────────────────

it('rejects a callback with no signature', function () {
    $invoice = paydunyaInvoice();

    postWebhook(paydunyaPayload($invoice, ['hash' => null]))
        ->assertStatus(403)
        ->assertJsonPath('message', 'Invalid signature');

    expect($invoice->fresh()->status)->toBe('PENDING');
    expect(Subscription::count())->toBe(0);
});

it('rejects a callback with a forged signature', function () {
    $invoice = paydunyaInvoice();

    postWebhook(paydunyaPayload($invoice, ['hash' => hash('sha512', 'wrong_key')]))
        ->assertStatus(403);

    expect($invoice->fresh()->status)->toBe('PENDING');
    expect(Subscription::count())->toBe(0);
});

it('rejects every callback when no master key is configured', function () {
    config()->set('services.paydunya.master_key', null);
    $invoice = paydunyaInvoice();

    postWebhook(paydunyaPayload($invoice))->assertStatus(403);

    expect($invoice->fresh()->status)->toBe('PENDING');
});

it('refuses the free-subscription payload a school owner could forge', function () {
    $invoice = paydunyaInvoice();

    // Exactly what an owner can build from their own invoice list, minus the
    // one thing they cannot compute: the master key digest.
    postWebhook(['custom_data' => ['invoice_id' => $invoice->id], 'status' => 'completed'])
        ->assertStatus(403);

    expect($invoice->fresh()->status)->toBe('PENDING');
    expect(Subscription::count())->toBe(0);
});

// ── Happy path ───────────────────────────────────────────────────

it('activates the subscription on a correctly signed completed payment', function () {
    $invoice = paydunyaInvoice();

    postWebhook(paydunyaPayload($invoice))
        ->assertOk()
        ->assertJsonPath('outcome', 'PAID');

    $invoice->refresh();
    expect($invoice->status)->toBe('PAID');
    expect($invoice->paid_at)->not->toBeNull();
    expect($invoice->subscription_id)->not->toBeNull();

    $payment = SubscriptionPayment::sole();
    expect($payment->status)->toBe('SUCCESS');
    expect($payment->payment_method)->toBe('WAVE');
    expect($payment->transaction_reference)->toBe('RCPT-'.$invoice->id);

    expect(Subscription::where('school_id', $invoice->school_id)->where('status', 'ACTIVE')->count())->toBe(1);
});

it('accepts a flat payload without the data wrapper', function () {
    $invoice = paydunyaInvoice();

    postWebhook(paydunyaPayload($invoice)['data'])
        ->assertOk()
        ->assertJsonPath('outcome', 'PAID');

    expect($invoice->fresh()->status)->toBe('PAID');
});

// ── Idempotence ──────────────────────────────────────────────────

it('does not activate a second subscription when the callback is replayed', function () {
    $invoice = paydunyaInvoice();
    $payload = paydunyaPayload($invoice);

    postWebhook($payload)->assertOk()->assertJsonPath('outcome', 'PAID');
    postWebhook($payload)->assertOk()->assertJsonPath('outcome', 'ALREADY_PROCESSED');
    postWebhook($payload)->assertOk()->assertJsonPath('outcome', 'ALREADY_PROCESSED');

    expect(SubscriptionPayment::count())->toBe(1);
    expect(Subscription::count())->toBe(1);
});

it('ignores a callback carrying a reference already seen on another invoice', function () {
    $first = paydunyaInvoice();
    $second = paydunyaInvoice();

    postWebhook(paydunyaPayload($first, ['receipt_id' => 'RCPT-SHARED']))->assertOk();

    postWebhook(paydunyaPayload($second, ['receipt_id' => 'RCPT-SHARED']))
        ->assertOk()
        ->assertJsonPath('outcome', 'ALREADY_PROCESSED');

    expect($second->fresh()->status)->toBe('PENDING');
    expect(SubscriptionPayment::count())->toBe(1);
});

// ── Montant ──────────────────────────────────────────────────────

it('refuses a payment smaller than the invoiced amount', function () {
    $invoice = paydunyaInvoice(15000.00);

    postWebhook(paydunyaPayload($invoice, ['invoice' => ['total_amount' => 100.00]]))
        ->assertOk()
        ->assertJsonPath('outcome', 'AMOUNT_MISMATCH');

    expect($invoice->fresh()->status)->toBe('PENDING');
    expect(SubscriptionPayment::count())->toBe(0);
    expect(Subscription::count())->toBe(0);
});

// ── Échecs et payloads invalides ─────────────────────────────────

it('records a failed payment without creating a subscription', function () {
    $invoice = paydunyaInvoice();

    postWebhook(paydunyaPayload($invoice, ['status' => 'failed']))
        ->assertOk()
        ->assertJsonPath('outcome', 'FAILED');

    expect($invoice->fresh()->status)->toBe('FAILED');
    expect(SubscriptionPayment::sole()->status)->toBe('FAILED');
    expect(Subscription::count())->toBe(0);
});

it('returns 400 when the invoice id is missing from custom data', function () {
    $invoice = paydunyaInvoice();

    postWebhook(paydunyaPayload($invoice, ['custom_data' => []]))->assertStatus(400);
});

it('returns 404 for an unknown invoice', function () {
    $invoice = paydunyaInvoice();

    postWebhook(paydunyaPayload($invoice, ['custom_data' => ['invoice_id' => 999999]]))
        ->assertStatus(404);
});
