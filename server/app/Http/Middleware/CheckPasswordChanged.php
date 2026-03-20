<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->password_changed_at === null) {
            return ApiResponse::error('Vous devez definir votre mot de passe avant de continuer.', 403, 'PASSWORD_CHANGE_REQUIRED');
        }

        return $next($request);
    }
}
