<?php

use App\Http\Controllers\Api\V1\Platform\SchoolRegistrationRequestController;
use App\Http\Controllers\Api\V1\Platform\SubscriptionController;
use Illuminate\Support\Facades\Route;

// ── Public platform routes ──────────────────────────────
Route::post('public/registration-requests', [SchoolRegistrationRequestController::class, 'store']);

// ── Authenticated + School Context + SuperAdmin ─────────
Route::middleware(['auth:sanctum', 'isolate.school', 'role:SUPER_ADMIN'])->prefix('platform')->group(function (): void {

    // School registration requests management
    Route::get('registration-requests', [SchoolRegistrationRequestController::class, 'index']);
    Route::patch('registration-requests/{registrationRequest}/approve', [SchoolRegistrationRequestController::class, 'approve']);
    Route::patch('registration-requests/{registrationRequest}/reject', [SchoolRegistrationRequestController::class, 'reject']);

    // Platform subscription management
    Route::get('subscriptions', [SubscriptionController::class, 'index']);
    Route::patch('subscriptions/{subscription}/suspend', [SubscriptionController::class, 'suspend']);
    Route::patch('subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel']);
});
