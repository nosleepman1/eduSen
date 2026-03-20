<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\SetPasswordRequest;
use App\Services\Api\V1\Auth\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class PasswordController extends Controller
{
    public function setPassword(SetPasswordRequest $request, AuthService $service): JsonResponse
    {
        $service->setPassword($request->validated());

        return ApiResponse::success('Votre mot de passe est defini. Vous pouvez maintenant vous connecter.');
    }
}
