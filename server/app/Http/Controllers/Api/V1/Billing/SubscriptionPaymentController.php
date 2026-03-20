<?php

namespace App\Http\Controllers\Api\V1\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Billing\SubscribeRequest;
use App\Models\BillingCycle;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Services\Api\V1\Billing\BillingService;
use App\Services\Api\V1\Billing\PayDunyaService;
use App\Services\Api\V1\Billing\PaymentConfirmationService;
use App\Support\ApiResponse;
use App\Support\TenantHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionPaymentController extends Controller
{
    public function __construct(
        private BillingService $billingService,
        private PayDunyaService $payDunyaService,
        private PaymentConfirmationService $paymentConfirmationService
    ) {}

    public function checkout(SubscribeRequest $request): JsonResponse
    {
        $school = TenantHelper::currentSchool();
        $plan = SubscriptionPlan::findOrFail($request->subscription_plan_id);
        $cycle = BillingCycle::findOrFail($request->billing_cycle_id);

        $pricing = $this->billingService->calculatePrice($plan, $cycle);

        // Create a pending invoice
        $invoice = SubscriptionInvoice::create([
            'school_id' => $school->id,
            'subscription_plan_id' => $plan->id,
            'billing_cycle_id' => $cycle->id,
            'amount' => $pricing['final_price'],
            'due_date' => now()->addDays(7),
        ]);

        // Generate PayDunya Checkout
        $checkoutData = $this->payDunyaService->createInvoice($invoice);

        if (! $checkoutData['success']) {
            return ApiResponse::error('Erreur lors de la génération du paiement: '.$checkoutData['error'], 500);
        }

        return ApiResponse::success('Checkout généré avec succès', [
            'invoice' => $invoice,
            'checkout_url' => $checkoutData['url'],
            'token' => $checkoutData['token'] ?? null,
        ]);
    }

    /**
     * Client-driven confirmation, called when the payer comes back from the
     * gateway. The invoice referenced by the gateway response is checked
     * against the caller's tenant before anything is applied — the token comes
     * from the client, so the invoice it resolves to cannot be trusted.
     */
    public function verifyPayment(Request $request): JsonResponse
    {
        $token = $request->query('token');

        if (! $token) {
            return ApiResponse::error('Token manquant', 400);
        }

        $result = $this->payDunyaService->confirmInvoice($token);

        if (! $result['success']) {
            return ApiResponse::error('Erreur de vérification: '.$result['error'], 500);
        }

        $invoiceId = $result['custom_data']['invoice_id'] ?? null;

        if (! $invoiceId) {
            return ApiResponse::success('Vérification complétée (facture non identifiée)', $result);
        }

        $invoice = SubscriptionInvoice::with(['school'])->find($invoiceId);

        if (! $invoice) {
            return ApiResponse::success('Vérification complétée (facture introuvable)', $result);
        }

        TenantHelper::assertOwnedByCurrentSchool($invoice->school_id);

        $outcome = $this->paymentConfirmationService->apply(
            $invoice,
            $this->payDunyaService->extractPaymentResult($result)
        );

        return ApiResponse::success('Paiement vérifié et appliqué', $result + ['outcome' => $outcome]);
    }
}
