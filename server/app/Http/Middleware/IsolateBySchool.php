<?php

namespace App\Http\Middleware;

use App\Support\TenantHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsolateBySchool
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        // SuperAdmin can pass a school context via header
        if ($user->isSuperAdmin()) {
            $schoolId = $request->header('X-School-Id');
            if ($schoolId) {
                TenantHelper::setCurrentSchool($schoolId);
            }

            return $next($request);
        }

        // For regular users, extract school_id from the Sanctum token abilities
        $token = $user->currentAccessToken();
        $schoolId = null;

        if ($token && method_exists($token, 'can')) {
            // Sanctum stores abilities — we look for school_id
            $abilities = $token->abilities ?? [];
            $schoolId = $abilities['school_id'] ?? null;
        }

        if ($schoolId) {
            TenantHelper::setCurrentSchool($schoolId);
            $request->attributes->set('school_id', $schoolId);
        }

        return $next($request);
    }
}
