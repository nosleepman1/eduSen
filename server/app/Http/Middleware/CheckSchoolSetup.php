<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use App\Support\TenantHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSchoolSetup
{
    public function handle(Request $request, Closure $next): Response
    {
        $school = TenantHelper::currentSchool();

        if ($school !== null && $school->is_school_setup === false) {
            return ApiResponse::error('La configuration de votre ecole est incomplete.', 403, 'SCHOOL_SETUP_REQUIRED', [
                'setup_step' => [$school->setup_step],
            ]);
        }

        return $next($request);
    }
}
