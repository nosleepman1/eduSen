<?php

namespace App\Services\Api\V1\Billing;

use App\Models\SubscriptionInvoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayDunyaService
{
    private string $masterKey;

    private string $privateKey;

    private string $token;

    private string $mode;

    private string $baseUrl;

    public function __construct()
    {
        $this->masterKey = (string) (config('services.paydunya.master_key') ?? '');
        $this->privateKey = (string) (config('services.paydunya.private_key') ?? '');
        $this->token = (string) (config('services.paydunya.token') ?? '');
        $this->mode = (string) (config('services.paydunya.mode') ?? 'test');
        $this->baseUrl = $this->mode === 'live'
            ? 'https://app.paydunya.com/api/v1'
            : 'https://app.paydunya.com/sandbox-api/v1';
    }

    /**
     * Dummy mode short-circuits every gateway call and reports payments as
     * completed. It is a local development convenience and must never be
     * reachable outside `local` and `testing`, whatever the env file says.
     */
    public function useDummyMode(): bool
    {
        return (bool) config('services.paydunya.use_dummy', false)
            && app()->environment(['local', 'testing']);
    }

    /**
     * PayDunya IPN callbacks wrap everything under a `data` key; the confirm
     * endpoint returns the same shape flat. Normalise both into one array.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function normalizeWebhookPayload(array $payload): array
    {
        return is_array($payload['data'] ?? null) ? $payload['data'] : $payload;
    }

    /**
     * PayDunya authenticates its IPN callbacks with a SHA-512 digest of the
     * merchant master key, sent as `hash`. Without a configured master key
     * there is nothing to verify against, so the callback is rejected.
     *
     * @param  array<string, mixed>  $payload  Already normalised payload.
     */
    public function isValidWebhookSignature(array $payload): bool
    {
        if ($this->masterKey === '') {
            Log::error('PayDunya: PAYDUNYA_MASTER_KEY absente, callback rejeté.');

            return false;
        }

        $received = $payload['hash'] ?? null;

        if (! is_string($received) || $received === '') {
            return false;
        }

        return hash_equals(hash('sha512', $this->masterKey), $received);
    }

    /**
     * Flatten a gateway payload into the shape PaymentConfirmationService expects.
     *
     * @param  array<string, mixed>  $payload  Already normalised payload.
     * @return array{status: string, reference: ?string, amount: ?float, method: ?string, payload: array<string, mixed>}
     */
    public function extractPaymentResult(array $payload): array
    {
        $amount = $payload['invoice']['total_amount'] ?? $payload['total_amount'] ?? null;

        return [
            'status' => strtolower((string) ($payload['status'] ?? 'pending')),
            'reference' => isset($payload['receipt_id']) ? (string) $payload['receipt_id'] : null,
            'amount' => is_numeric($amount) ? (float) $amount : null,
            'method' => $payload['customer']['payment_method'] ?? null,
            'payload' => $payload,
        ];
    }

    /**
     * Create a Checkout Invoice (PER) in PayDunya
     *
     * @return array Returns ['success' => bool, 'url' => string|null, 'token' => string|null, 'error' => string|null]
     */
    public function createInvoice(SubscriptionInvoice $invoice, array $returnUrls = []): array
    {
        $payload = [
            'invoice' => [
                'total_amount' => $invoice->amount,
                'description' => 'Abonnement EduSen - '.$invoice->school->name,
                'items' => [
                    [
                        'name' => 'Abonnement',
                        'quantity' => 1,
                        'unit_price' => $invoice->amount,
                        'total_price' => $invoice->amount,
                        'description' => 'Facture '.$invoice->invoice_number,
                    ],
                ],
            ],
            'store' => [
                'name' => 'EduSen',
                'tagline' => 'Plateforme de gestion scolaire',
                'postal_address' => 'Dakar, Senegal',
                'website_url' => config('app.url'),
            ],
            'custom_data' => [
                'invoice_id' => $invoice->id,
                'uuid' => $invoice->uuid,
                'school_id' => $invoice->school_id,
            ],
            'actions' => [
                'cancel_url' => $returnUrls['cancel_url'] ?? config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')).'/payment/cancel',
                'return_url' => $returnUrls['return_url'] ?? config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')).'/payment/success',
                'callback_url' => route('api.v1.webhooks.paydunya.subscription'),
            ],
        ];

        // In test mode, we might want to return a dummy URL immediately if we don't have real keys
        if ($this->useDummyMode()) {
            Log::info('PayDunyaService: Using dummy mode for invoice '.$invoice->invoice_number);

            return [
                'success' => true,
                'url' => 'https://dummy.paydunya.com/checkout/'.$invoice->uuid,
                'token' => 'dummy_token_'.$invoice->uuid,
            ];
        }

        try {
            $response = Http::withHeaders([
                'PAYDUNYA-MASTER-KEY' => $this->masterKey,
                'PAYDUNYA-PRIVATE-KEY' => $this->privateKey,
                'PAYDUNYA-TOKEN' => $this->token,
            ])->post($this->baseUrl.'/checkout-invoice/create', $payload);

            if ($response->successful() && $response->json('response_code') === '00') {
                return [
                    'success' => true,
                    'url' => $response->json('response_text'), // PayDunya returns the URL in response_text
                    'token' => $response->json('token'),
                ];
            }

            Log::error('PayDunya API Error: '.$response->body());

            return [
                'success' => false,
                'error' => 'Erreur lors de la création de la facture PayDunya.',
            ];
        } catch (\Exception $e) {
            Log::error('PayDunya Exception: '.$e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function confirmInvoice(string $token): array
    {
        if ($this->useDummyMode()) {
            $uuid = str_replace('dummy_token_', '', $token);
            $invoice = SubscriptionInvoice::where('uuid', $uuid)->first();

            return [
                'success' => true,
                'status' => 'completed',
                'amount' => $invoice ? (float) $invoice->amount : 0,
                'total_amount' => $invoice ? (float) $invoice->amount : 0,
                'receipt_id' => 'dummy_receipt_'.$uuid,
                'custom_data' => [
                    'invoice_id' => $invoice ? $invoice->id : null,
                ],
            ];
        }

        try {
            $response = Http::withHeaders([
                'PAYDUNYA-MASTER-KEY' => $this->masterKey,
                'PAYDUNYA-PRIVATE-KEY' => $this->privateKey,
                'PAYDUNYA-TOKEN' => $this->token,
            ])->get($this->baseUrl.'/checkout-invoice/confirm/'.$token);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'status' => $response->json('status'),
                    'amount' => $response->json('total_amount'),
                    'receipt_id' => $response->json('receipt_id'),
                    'custom_data' => $response->json('custom_data') ?? [],
                    'raw' => $response->json(),
                ];
            }

            return ['success' => false, 'error' => 'API Error'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
