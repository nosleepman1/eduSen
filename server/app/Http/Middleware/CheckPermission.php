<?php

namespace App\Http\Middleware;

use App\Services\Api\V1\Auth\PermissionService;
use App\Support\ApiResponse;
use App\Support\TenantHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function __construct(
        private readonly PermissionService $permissions
    ) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponse::error('Authentification requise.', 401, 'UNAUTHENTICATED');
        }

        // SuperAdmin bypasses all permission checks
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $schoolId = TenantHelper::currentSchoolId();

        if (! $schoolId) {
            return ApiResponse::error('Aucun contexte école actif.', 403, 'NO_SCHOOL_CONTEXT');
        }

        if ($this->permissions->activeRolesIn($user, $schoolId) === []) {
            return ApiResponse::error('Aucun rôle actif dans cette école.', 403, 'NO_ACTIVE_ROLE');
        }

        // Resolved through PermissionService so that what is enforced here and
        // what the login response advertises can never diverge.
        $granted = $this->permissions->effectiveFor($user, $schoolId);

        if (! $this->permissions->allows($granted, $permission)) {
            return ApiResponse::error("Vous n'avez pas la permission : {$permission}.", 403, 'PERMISSION_DENIED');
        }

        return $next($request);
    }
}
