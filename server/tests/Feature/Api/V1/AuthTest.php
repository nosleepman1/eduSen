<?php

use App\Enums\RoleEnum;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

it('lets an owner set a password with a valid token', function (): void {
    $owner = User::factory()->create(['password' => null, 'password_changed_at' => null]);
    $token = Password::broker()->createToken($owner);

    $this->postJson('/api/v1/auth/set-password', [
        'email' => $owner->email,
        'token' => $token,
        'password' => 'password-secret',
        'password_confirmation' => 'password-secret',
    ])->assertOk();

    expect($owner->refresh()->password_changed_at)->not->toBeNull();
});

it('rejects invalid set password payloads', function (): void {
    $owner = User::factory()->create();

    $this->postJson('/api/v1/auth/set-password', [
        'email' => $owner->email,
        'token' => 'invalid',
        'password' => 'short',
        'password_confirmation' => 'different',
    ])->assertUnprocessable();
});

it('logs in an active user and returns setup flags', function (): void {
    $owner = User::factory()->create(['password' => Hash::make('secret-password')]);
    $school = School::factory()->create([
        'owner_id' => $owner->id,
        'is_school_setup' => false,
        'setup_step' => 1,
    ]);
    
    \App\Models\Personnel::create([
        'user_id' => $owner->id,
        'school_id' => $school->id,
        'status' => \App\Enums\PersonnelStatusEnum::Active,
        'registration_number' => 'EMP-001',
    ]);
    
    \App\Models\UserRole::create([
        'user_id' => $owner->id,
        'school_id' => $school->id,
        'role' => RoleEnum::Owner,
        'is_active' => true,
        'assigned_at' => now(),
    ]);

    $this->postJson('/api/v1/auth/login', ['email' => $owner->email, 'password' => 'secret-password'])
        ->assertOk()
        ->assertJsonPath('data.mustChangePassword', false)
        ->assertJsonPath('data.schoolSetupRequired', true);
});

it('rejects bad credentials and inactive users', function (): void {
    $user = User::factory()->create(['password' => Hash::make('secret-password')]);

    $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'bad-password'])->assertUnauthorized();

    $user->update(['active' => false]);
    $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'secret-password'])->assertForbidden();
});
