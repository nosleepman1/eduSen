<?php

namespace App\Http\Controllers\Api\V1\Platform;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Enums\SubscriptionStatusEnum;

class SubscriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Subscription::with(['school', 'plan', 'billingCycle']);

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('school_id')) {
            $query->where('school_id', $request->query('school_id'));
        }

        $subscriptions = $query->latest()->paginate(15);

        return ApiResponse::success('Liste des abonnements récupérée avec succès.', $subscriptions);
    }

    public function suspend(Subscription $subscription): JsonResponse
    {
        if ($subscription->status === SubscriptionStatusEnum::Suspended) {
            return ApiResponse::error('L\'abonnement est déjà suspendu.', 400);
        }

        $subscription->update([
            'status' => SubscriptionStatusEnum::Suspended,
            'auto_renew' => false,
        ]);

        return ApiResponse::success('Abonnement suspendu avec succès.', [
            'subscription' => $subscription->load(['school', 'plan', 'billingCycle']),
        ]);
    }

    public function cancel(Subscription $subscription, Request $request): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|min:10',
        ]);

        if ($subscription->status === SubscriptionStatusEnum::Cancelled) {
            return ApiResponse::error('L\'abonnement est déjà annulé.', 400);
        }

        $subscription->update([
            'status' => SubscriptionStatusEnum::Cancelled,
            'auto_renew' => false,
            'cancelled_at' => now(),
            'cancellation_reason' => $request->reason,
        ]);

        return ApiResponse::success('Abonnement annulé avec succès.', [
            'subscription' => $subscription->load(['school', 'plan', 'billingCycle']),
        ]);
    }
}
