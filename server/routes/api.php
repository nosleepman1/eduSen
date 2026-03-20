<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    
    // Auth domain (Login, Select School, Me)
    Route::prefix('auth')->group(function (): void {
        require __DIR__ . '/api/v1/auth.php';
    });

    // Platform domain (SuperAdmin, Public Registration Requests)
    require __DIR__ . '/api/v1/platform.php';

    // School domain (Tenants, Invitations, Students, Setup)
    require __DIR__ . '/api/v1/school.php';

    // Academic domain (Years, Periods, Calendar, Specializations)
    require __DIR__ . '/api/v1/academic.php';

    // Billing domain (Subscriptions, Plans)
    require __DIR__ . '/api/v1/subscription.php';

    // Webhooks
    Route::prefix('webhooks')->group(function (): void {
        Route::post('paydunya/subscription', [\App\Http\Controllers\Api\V1\Webhooks\PayDunyaWebhookController::class, 'handleSubscriptionPayment'])
            ->name('api.v1.webhooks.paydunya.subscription');
    });

});
