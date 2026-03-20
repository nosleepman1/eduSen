<?php

namespace App\Http\Middleware;

use App\Enums\SubscriptionStatusEnum;
use App\Models\School;
use App\Models\Subscription;
use App\Support\ApiResponse;
use App\Support\TenantHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isSuperAdmin()) {
            return $next($request);
        }

        $school = TenantHelper::currentSchool();
        if (!$school) {
            return ApiResponse::error('Aucune école associée à cet utilisateur.', 403, 'NO_SCHOOL_ASSOCIATED');
        }

        $subscription = $school->subscriptions()
            ->whereIn('status', [
                SubscriptionStatusEnum::Active,
                SubscriptionStatusEnum::Trial,
                SubscriptionStatusEnum::GracePeriod,
                SubscriptionStatusEnum::TrialExpired,
                SubscriptionStatusEnum::Cancelled
            ])
            ->where(function ($query) {
                $query->where('ends_at', '>=', now())
                    ->orWhere('grace_ends_at', '>=', now());
            })
            ->orderBy('ends_at', 'desc')
            ->first();

        if (!$subscription) {
            return ApiResponse::error('Subscription inactive', 403, 'SUBSCRIPTION_INACTIVE');
        }

        $inGracePeriod = false;
        if (in_array($subscription->status, [SubscriptionStatusEnum::TrialExpired, SubscriptionStatusEnum::GracePeriod])) {
            $inGracePeriod = $subscription->grace_ends_at && $subscription->grace_ends_at->isFuture();
        } elseif ($subscription->status === SubscriptionStatusEnum::Cancelled && $subscription->ends_at->isPast()) {
            return ApiResponse::error('Subscription inactive', 403, 'SUBSCRIPTION_INACTIVE');
        }

        if ($inGracePeriod) {
            if (!$request->isMethod('GET')) {
                return ApiResponse::error('Votre abonnement a expiré. Mode lecture seule uniquement. Veuillez renouveler votre abonnement.', 403, 'SUBSCRIPTION_GRACE_READONLY');
            }

            $response = $next($request);
            $response->headers->set('X-Subscription-Warning', 'Abonnement expire. Mode lecture seule actif.');
            return $response;
        }

        return $next($request);
    }
}
