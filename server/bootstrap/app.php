<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'password.changed' => \App\Http\Middleware\CheckPasswordChanged::class,
            'school.setup' => \App\Http\Middleware\CheckSchoolSetup::class,
            'subscription.active' => \App\Http\Middleware\CheckSubscription::class,
            'isolate.school' => \App\Http\Middleware\IsolateBySchool::class,
            'role' => \App\Http\Middleware\CheckRole::class,
            'feature' => \App\Http\Middleware\EnsureFeatureAccess::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if ($request->is('api/*')) {
                return \App\Support\ApiResponse::error('Les donnees fournies sont invalides.', 422, null, $exception->errors());
            }
        });
        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if ($request->is('api/*')) {
                return \App\Support\ApiResponse::error($exception->getMessage() ?: 'Authentification requise.', 401, 'UNAUTHENTICATED');
            }
        });
        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            if ($request->is('api/*')) {
                return \App\Support\ApiResponse::error($exception->getMessage() ?: 'Action non autorisee.', 403, 'FORBIDDEN');
            }
        });
        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if ($request->is('api/*') && in_array($exception->getStatusCode(), [401, 403], true)) {
                return \App\Support\ApiResponse::error($exception->getMessage(), $exception->getStatusCode(), $exception->getStatusCode() === 401 ? 'UNAUTHENTICATED' : 'FORBIDDEN');
            }
        });
    })->create();
