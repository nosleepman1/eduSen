<?php

use App\Models\User;
use App\Models\School;
use App\Models\Personnel;
use App\Enums\PersonnelStatusEnum;
use Illuminate\Support\Facades\Hash;

it('auto-selects school and returns full token if user only belongs to one school', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password123'),
        'status' => \App\Enums\UserStatusEnum::Active,
        'password_changed_at' => now(),
    ]);

    $school = School::factory()->create(['is_school_setup' => true]);
    Personnel::create([
        'user_id' => $user->id, 
        'school_id' => $school->id, 
        'status' => PersonnelStatusEnum::Active,
        'registration_number' => 'EMP-001'
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
             ->assertJsonPath('data.type', 'single_school')
             ->assertJsonPath('data.school.id', $school->id);
             
    expect($response->json('data.token'))->not->toBeNull();
});

it('returns temporary token and school list if user belongs to multiple schools', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password123'),
        'status' => \App\Enums\UserStatusEnum::Active,
    ]);

    $school1 = School::factory()->create();
    $school2 = School::factory()->create();
    
    Personnel::create(['user_id' => $user->id, 'school_id' => $school1->id, 'status' => PersonnelStatusEnum::Active, 'registration_number' => 'EMP-002']);
    Personnel::create(['user_id' => $user->id, 'school_id' => $school2->id, 'status' => PersonnelStatusEnum::Active, 'registration_number' => 'EMP-003']);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
             ->assertJsonPath('data.type', 'multi_school')
             ->assertJsonCount(2, 'data.schools');
});
