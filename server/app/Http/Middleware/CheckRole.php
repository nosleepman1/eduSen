<?php

namespace App\Http\Middleware;

use App\Enums\RoleEnum;
use App\Models\UserRole;
use App\Support\ApiResponse;
use App\Support\TenantHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponse::error('Authentification requise.', 401, 'UNAUTHENTICATED');
        }

        // SuperAdmin bypasses all role checks
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $schoolId = TenantHelper::currentSchoolId();

        if (! $schoolId) {
            return ApiResponse::error('Aucun contexte école actif.', 403, 'NO_SCHOOL_CONTEXT');
        }

        // Check if the user has ANY of the required roles in this school
        $hasRole = UserRole::where('user_id', $user->id)
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->whereIn('role', $roles)
            ->exists();

        if (! $hasRole) {
            return ApiResponse::error('Vous n\'etes pas autorise a effectuer cette action.', 403, 'FORBIDDEN');
        }

        return $next($request);
    }
}
