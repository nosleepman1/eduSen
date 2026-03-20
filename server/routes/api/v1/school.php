<?php

use App\Http\Controllers\Api\V1\School\GuardianController;
use App\Http\Controllers\Api\V1\School\InvitationController;
use App\Http\Controllers\Api\V1\School\PersonnelController;
use App\Http\Controllers\Api\V1\School\SchoolSetupController;
use App\Http\Controllers\Api\V1\School\StudentController;
use App\Http\Controllers\Api\V1\School\TeacherController;
use Illuminate\Support\Facades\Route;

// ── Public school routes ─────────────────────────────────
Route::post('invitations/accept', [InvitationController::class, 'accept']);

// ── Authenticated + School Context ───────────────────────
Route::middleware(['auth:sanctum', 'isolate.school'])->group(function (): void {

    // OWNER routes — School setup (requires password changed)
    Route::middleware(['role:OWNER', 'password.changed'])->prefix('school/setup')->group(function (): void {
        Route::post('basic-info', [SchoolSetupController::class, 'basicInfo']);
        Route::post('contact-info', [SchoolSetupController::class, 'contactInfo']);
        Route::post('complete', [SchoolSetupController::class, 'complete']);
    });

    // ADMINISTRATION routes (Owner + Admin)
    Route::middleware(['role:OWNER,ADMIN', 'subscription.active'])->group(function (): void {
        Route::get('dashboard/stats', [\App\Http\Controllers\Api\V1\School\SchoolDashboardController::class, 'stats']);
        
        Route::apiResource('invitations', InvitationController::class)->except(['update']);
        Route::post('invitations/{invitation}/resend', [InvitationController::class, 'resend']);
        Route::post('invitations/{invitation}/cancel', [InvitationController::class, 'cancel']);
    });

    // PERSONNEL routes
    Route::middleware('subscription.active')->group(function (): void {
        Route::middleware('permission:personnel.view')->group(function (): void {
            Route::get('personnel', [PersonnelController::class, 'index']);
            Route::get('personnel/{personnel}', [PersonnelController::class, 'show']);
        });

        Route::middleware('permission:personnel.update')->group(function (): void {
            Route::put('personnel/{personnel}', [PersonnelController::class, 'update']);
            Route::patch('personnel/{personnel}/roles', [PersonnelController::class, 'updateRoles']);
        });

        Route::middleware('permission:personnel.delete')->group(function (): void {
            Route::delete('personnel/{personnel}', [PersonnelController::class, 'destroy']);
            Route::patch('personnel/{id}/restore', [PersonnelController::class, 'restore']);
        });

        // STUDENT routes — creation/editing restricted to OWNER/ADMIN/SECRETARY (RPR-004)
        Route::middleware('permission:student.view')->group(function (): void {
            Route::get('students', [StudentController::class, 'index']);
            Route::get('students/{student}', [StudentController::class, 'show']);
        });

        Route::middleware('permission:student.create')->group(function (): void {
            Route::post('students', [StudentController::class, 'store']);
            Route::post('students/{student}/guardians', [StudentController::class, 'linkGuardian']);
        });

        Route::middleware('permission:student.update')->put('students/{student}', [StudentController::class, 'update']);

        Route::middleware('permission:student.delete')->group(function (): void {
            Route::delete('students/{student}', [StudentController::class, 'destroy']);
            Route::patch('students/{id}/restore', [StudentController::class, 'restore']);
        });

        // TEACHER routes
        Route::middleware('permission:teacher.view')->group(function (): void {
            Route::get('teachers', [TeacherController::class, 'index']);
            Route::get('teachers/{teacher}', [TeacherController::class, 'show']);
        });

        Route::middleware('permission:teacher.create')->post('teachers', [TeacherController::class, 'store']);
        Route::middleware('permission:teacher.update')->put('teachers/{teacher}', [TeacherController::class, 'update']);

        Route::middleware('permission:teacher.delete')->group(function (): void {
            Route::delete('teachers/{teacher}', [TeacherController::class, 'destroy']);
            Route::patch('teachers/{id}/restore', [TeacherController::class, 'restore']);
        });

        // GUARDIAN (parent) routes
        Route::middleware('permission:parent.view')->group(function (): void {
            Route::get('guardians', [GuardianController::class, 'index']);
            Route::get('guardians/{guardian}', [GuardianController::class, 'show']);
        });

        Route::middleware('permission:parent.create')->post('guardians', [GuardianController::class, 'store']);
        Route::middleware('permission:parent.update')->put('guardians/{guardian}', [GuardianController::class, 'update']);

        Route::middleware('permission:parent.delete')->group(function (): void {
            Route::delete('guardians/{guardian}', [GuardianController::class, 'destroy']);
            Route::patch('guardians/{id}/restore', [GuardianController::class, 'restore']);
        });
    });
});
