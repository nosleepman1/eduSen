<?php

namespace App\Http\Middleware;

use App\Services\Api\V1\Billing\FeatureAccessService;
use App\Support\ApiResponse;
use App\Support\TenantHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeatureAccess
{
    public function __construct(
        private FeatureAccessService $featureAccessService
    ) {}

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if ($user?->isSuperAdmin()) {
            return $next($request);
        }

        $school = TenantHelper::currentSchool();
        if (!$school) {
            return ApiResponse::error('Aucune école associée à cet utilisateur.', 403, 'NO_SCHOOL_ASSOCIATED');
        }

        if (!$this->featureAccessService->hasFeature($school, $feature)) {
            return ApiResponse::error("Votre plan d'abonnement n'inclut pas l'accès à la fonctionnalité : {$feature}.", 403, 'FEATURE_ACCESS_DENIED');
        }

        return $next($request);
    }
}
