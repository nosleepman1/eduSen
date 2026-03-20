<?php

namespace App\Http\Controllers\Api\V1\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionInvoice;
use App\Services\Api\V1\Billing\PayDunyaService;
use App\Services\Api\V1\Billing\PaymentConfirmationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayDunyaWebhookController extends Controller
{
    public function __construct(
        private readonly PayDunyaService $payDunyaService,
        private readonly PaymentConfirmationService $paymentConfirmationService
    ) {}

    /**
     * PayDunya IPN callback. This is the only unauthenticated endpoint of the
     * API: the SHA-512 signature check below is what stands between a caller
     * and a free subscription, so it runs before anything else is read.
     */
    public function handleSubscriptionPayment(Request $request): JsonResponse
    {
        $payload = $this->payDunyaService->normalizeWebhookPayload($request->all());

        if (! $this->payDunyaService->isValidWebhookSignature($payload)) {
            Log::warning('PayDunya Webhook: signature invalide.', ['ip' => $request->ip()]);

            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 403);
        }

        $invoiceId = $payload['custom_data']['invoice_id'] ?? null;

        if (! $invoiceId) {
            Log::error('PayDunya Webhook: invoice_id absent des custom_data.', $payload);

            return response()->json(['status' => 'error', 'message' => 'Missing invoice ID'], 400);
        }

        $invoice = SubscriptionInvoice::with('school')->find($invoiceId);

        if (! $invoice) {
            Log::error('PayDunya Webhook: facture introuvable.', ['invoice_id' => $invoiceId]);

            return response()->json(['status' => 'error', 'message' => 'Invoice not found'], 404);
        }

        $outcome = $this->paymentConfirmationService->apply(
            $invoice,
            $this->payDunyaService->extractPaymentResult($payload)
        );

        return response()->json(['status' => 'success', 'outcome' => $outcome]);
    }
}
