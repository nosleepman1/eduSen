<?php

namespace App\Http\Controllers\Api\V1\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Billing\CancelSubscriptionRequest;
use App\Http\Requests\Api\V1\Billing\RenewSubscriptionRequest;
use App\Http\Requests\Api\V1\Billing\SubscribeRequest;
use App\Models\BillingCycle;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\Api\V1\Billing\SubscriptionService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService
    ) {}

    public function current(Request $request): JsonResponse
    {
        $school = \App\Support\TenantHelper::currentSchool();
        if (!$school) {
            return ApiResponse::error('Aucune école associée à cet utilisateur.', 403, 'NO_SCHOOL_ASSOCIATED');
        }

        $current = $school->subscriptions()
            ->with(['plan', 'billingCycle'])
            ->orderBy('ends_at', 'desc')
            ->first();

        $history = $school->subscriptions()
            ->with(['plan', 'billingCycle'])
            ->orderBy('created_at', 'desc')
            ->get();

        $invoices = \App\Models\SubscriptionInvoice::where('school_id', $school->id)
            ->with(['subscription.plan'])
            ->orderBy('created_at', 'desc')
            ->get();

        return ApiResponse::success('Informations sur votre abonnement actuel.', [
            'current' => $current,
            'history' => $history,
            'invoices' => $invoices,
        ]);
    }

    public function subscribe(SubscribeRequest $request): JsonResponse
    {
        $school = \App\Support\TenantHelper::currentSchool();
        $plan = SubscriptionPlan::findOrFail($request->subscription_plan_id);
        $cycle = BillingCycle::findOrFail($request->billing_cycle_id);

        $subscription = $this->subscriptionService->subscribe($school, $plan, $cycle);

        return ApiResponse::success('Abonnement souscrit avec succès.', [
            'subscription' => $subscription->load(['plan', 'billingCycle']),
        ]);
    }

    public function renew(RenewSubscriptionRequest $request): JsonResponse
    {
        $subscription = Subscription::findOrFail($request->subscription_id);

        if ($subscription->school_id !== \App\Support\TenantHelper::currentSchoolId()) {
            return ApiResponse::error('Action non autorisée.', 403, 'FORBIDDEN');
        }

        $newSubscription = $this->subscriptionService->renew($subscription);

        return ApiResponse::success('Abonnement renouvelé avec succès.', [
            'subscription' => $newSubscription->load(['plan', 'billingCycle']),
        ]);
    }

    public function cancel(CancelSubscriptionRequest $request): JsonResponse
    {
        $school = \App\Support\TenantHelper::currentSchool();
        if (!$school) {
            return ApiResponse::error('Aucune école associée.', 403);
        }

        $current = $school->subscriptions()
            ->where('ends_at', '>=', now())
            ->orderBy('ends_at', 'desc')
            ->first();

        if (!$current) {
            return ApiResponse::error("Aucun abonnement actif à annuler.", 404);
        }

        $cancelledSubscription = $this->subscriptionService->cancel($current, $request->reason);

        return ApiResponse::success('Abonnement annulé avec succès.', [
            'subscription' => $cancelledSubscription->load(['plan', 'billingCycle']),
        ]);
    }
}
