<?php

use App\Models\School;
use App\Models\User;
use App\Models\Personnel;
use App\Models\UserRole;
use App\Enums\RoleEnum;
use Laravel\Sanctum\Sanctum;

it('blocks access if user tries to access a school they do not belong to', function () {
    $user = User::factory()->create();
    
    // School A (User belongs here)
    $schoolA = School::factory()->create();
    Personnel::create(['user_id' => $user->id, 'school_id' => $schoolA->id, 'registration_number' => 'EMP-001']);
    UserRole::create(['user_id' => $user->id, 'school_id' => $schoolA->id, 'role' => RoleEnum::Teacher, 'is_active' => true, 'assigned_at' => now()]);

    // School B (User does NOT belong here)
    $schoolB = School::factory()->create();

    // Create a real token to avoid Sanctum::actingAs TransientToken limitations
    $token = $user->createToken('api', ['school_id' => $schoolB->id])->plainTextToken;

    // Make request to an isolated route
    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->getJson('/api/v1/invitations'); 
    
    // CheckRole or CheckPermission should block it because the user has no active roles in School B
    $response->assertStatus(403);
    expect($response->json('message'))->toContain('Vous n\'etes pas autorise');
});

it('allows super admin to bypass tenant isolation and role checks', function () {
    $superAdmin = User::factory()->create();
    UserRole::create(['user_id' => $superAdmin->id, 'school_id' => null, 'role' => RoleEnum::SuperAdmin, 'is_active' => true, 'assigned_at' => now()]);

    Sanctum::actingAs($superAdmin, ['role' => 'SUPER_ADMIN']);

    $school = School::factory()->create();

    // Pass the target school ID in the header as Super Admin
    $response = $this->getJson('/api/v1/platform/subscriptions', [
        'X-School-Id' => $school->id
    ]);

    // Since SuperAdmin is allowed to hit platform routes, it should pass auth
    // (We just test that it doesn't get a 403 Forbidden from role checks)
    $response->assertStatus(200); 
});
