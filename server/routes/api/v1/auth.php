<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\PasswordController;
use Illuminate\Support\Facades\Route;

// ── Public routes (no auth) ──────────────────────────────
Route::post('login', [AuthController::class, 'login']);
Route::post('set-password', [PasswordController::class, 'setPassword']);

// ── Authenticated routes (before school selection) ───────
Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('select-school', [AuthController::class, 'selectSchool']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
});
