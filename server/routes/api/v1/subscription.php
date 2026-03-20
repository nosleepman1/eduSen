<?php

use App\Http\Controllers\Api\V1\Billing\BillingCycleController;
use App\Http\Controllers\Api\V1\Billing\SubscriptionController;
use App\Http\Controllers\Api\V1\Billing\SubscriptionPlanController;
use Illuminate\Support\Facades\Route;

// Public or standard access routes
Route::get('subscription/plans', [SubscriptionPlanController::class, 'index']);
Route::get('subscription/billing-cycles', [BillingCycleController::class, 'index']);

// Protected routes requiring authentication, school isolation, and owner role
Route::middleware(['auth:sanctum', 'isolate.school', 'role:OWNER'])->group(function (): void {
    Route::get('subscription/current', [SubscriptionController::class, 'current']);
    Route::post('subscription/checkout', [\App\Http\Controllers\Api\V1\Billing\SubscriptionPaymentController::class, 'checkout']);
    Route::post('subscription/verify-payment', [\App\Http\Controllers\Api\V1\Billing\SubscriptionPaymentController::class, 'verifyPayment']);
    Route::post('subscription/subscribe', [SubscriptionController::class, 'subscribe']); // Left for backward compatibility or internal use
    Route::post('subscription/renew', [SubscriptionController::class, 'renew']);
    Route::post('subscription/cancel', [SubscriptionController::class, 'cancel']);
});
