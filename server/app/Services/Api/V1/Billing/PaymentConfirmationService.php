<?php

namespace App\Services\Api\V1\Billing;

use App\Models\BillingCycle;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Applies a payment gateway result to an invoice.
 *
 * This is the single place where an invoice becomes PAID and a subscription
 * gets activated. Both entry points (the PayDunya IPN callback and the
 * client-driven verify-payment endpoint) go through it, so the idempotency,
 * amount and transaction guarantees below hold whichever door is used.
 */
class PaymentConfirmationService
{
    public const OUTCOME_PAID = 'PAID';

    public const OUTCOME_FAILED = 'FAILED';

    public const OUTCOME_PENDING = 'PENDING';

    public const OUTCOME_ALREADY_PROCESSED = 'ALREADY_PROCESSED';

    public const OUTCOME_AMOUNT_MISMATCH = 'AMOUNT_MISMATCH';

    private const GATEWAY = 'PAYDUNYA';

    public function __construct(
        private readonly SubscriptionService $subscriptionService
    ) {}

    /**
     * @param  array{status: string, reference: ?string, amount: ?float, method: ?string, payload: array<string, mixed>}  $result
     * @return self::OUTCOME_*
     */
    public function apply(SubscriptionInvoice $invoice, array $result): string
    {
        return DB::transaction(function () use ($invoice, $result): string {
            // Serialise concurrent deliveries of the same callback: the gateway
            // retries on timeout, and a double activation must be impossible.
            $invoice = SubscriptionInvoice::query()
                ->with('school')
                ->whereKey($invoice->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($this->isAlreadyProcessed($invoice, $result['reference'])) {
                return self::OUTCOME_ALREADY_PROCESSED;
            }

            if (! $this->isSuccessful($result['status'])) {
                return $this->recordFailure($invoice, $result);
            }

            if (! $this->amountMatches($invoice, $result['amount'])) {
                Log::warning('PayDunya: montant du paiement différent du montant facturé.', [
                    'invoice_id' => $invoice->id,
                    'invoice_amount' => $invoice->amount,
                    'paid_amount' => $result['amount'],
                ]);

                return self::OUTCOME_AMOUNT_MISMATCH;
            }

            return $this->recordSuccess($invoice, $result);
        });
    }

    /**
     * An invoice is processed once. The gateway reference is the strongest
     * signal (it survives an invoice being reopened), the PAID status covers
     * callbacks that arrive without one.
     */
    private function isAlreadyProcessed(SubscriptionInvoice $invoice, ?string $reference): bool
    {
        if ($invoice->status === 'PAID') {
            return true;
        }

        if ($reference === null || $reference === '') {
            return false;
        }

        return SubscriptionPayment::query()
            ->where('payment_gateway', self::GATEWAY)
            ->where('transaction_reference', $reference)
            ->exists();
    }

    private function isSuccessful(string $status): bool
    {
        return in_array($status, ['completed', 'successful'], true);
    }

    /**
     * A missing amount means the gateway did not report one — we fall back to
     * trusting the callback. A reported amount below the invoice is refused.
     */
    private function amountMatches(SubscriptionInvoice $invoice, ?float $paidAmount): bool
    {
        if ($paidAmount === null) {
            return true;
        }

        return $paidAmount >= ((float) $invoice->amount) - 0.01;
    }

    /**
     * @param  array{status: string, reference: ?string, amount: ?float, method: ?string, payload: array<string, mixed>}  $result
     * @return self::OUTCOME_PAID
     */
    private function recordSuccess(SubscriptionInvoice $invoice, array $result): string
    {
        SubscriptionPayment::create([
            'subscription_invoice_id' => $invoice->id,
            'school_id' => $invoice->school_id,
            'payment_gateway' => self::GATEWAY,
            'payment_method' => $result['method'],
            'transaction_reference' => $result['reference'],
            'amount' => $invoice->amount,
            'currency' => $invoice->currency,
            'status' => 'SUCCESS',
            'payload' => $result['payload'],
        ]);

        $invoice->update([
            'status' => 'PAID',
            'paid_at' => now(),
        ]);

        $plan = SubscriptionPlan::find($invoice->subscription_plan_id);
        $cycle = BillingCycle::find($invoice->billing_cycle_id);

        if ($plan && $cycle && $invoice->school) {
            $subscription = $this->subscriptionService->subscribe($invoice->school, $plan, $cycle);
            $invoice->update(['subscription_id' => $subscription->id]);
        }

        return self::OUTCOME_PAID;
    }

    /**
     * @param  array{status: string, reference: ?string, amount: ?float, method: ?string, payload: array<string, mixed>}  $result
     * @return self::OUTCOME_FAILED|self::OUTCOME_PENDING
     */
    private function recordFailure(SubscriptionInvoice $invoice, array $result): string
    {
        if ($result['status'] === 'pending') {
            return self::OUTCOME_PENDING;
        }

        SubscriptionPayment::create([
            'subscription_invoice_id' => $invoice->id,
            'school_id' => $invoice->school_id,
            'payment_gateway' => self::GATEWAY,
            'payment_method' => $result['method'],
            'transaction_reference' => $result['reference'],
            'amount' => $invoice->amount,
            'currency' => $invoice->currency,
            'status' => 'FAILED',
            'payload' => $result['payload'],
        ]);

        $invoice->update(['status' => 'FAILED']);

        return self::OUTCOME_FAILED;
    }
}
