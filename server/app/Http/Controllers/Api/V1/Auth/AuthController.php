<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\SelectSchoolRequest;
use App\Services\Api\V1\Auth\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(LoginRequest $request, AuthService $service): JsonResponse
    {
        return ApiResponse::success('Connexion reussie.', $service->login($request->validated()));
    }

    public function selectSchool(SelectSchoolRequest $request, AuthService $service): JsonResponse
    {
        return ApiResponse::success('École sélectionnée.', $service->selectSchool($request->user(), $request->validated('school_id')));
    }

    public function me(Request $request, AuthService $service): JsonResponse
    {
        return ApiResponse::success('Utilisateur courant.', $service->me($request->user()));
    }

    public function logout(Request $request, AuthService $service): JsonResponse
    {
        $service->logout($request->user());

        return ApiResponse::success('Déconnexion réussie.');
    }
}
