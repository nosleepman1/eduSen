<?php

use App\Enums\RoleEnum;
use App\Models\School;
use App\Models\SchoolRegistrationRequest;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Notifications\RegistrationApprovedNotification;
use Illuminate\Support\Facades\Notification;

function registrationPayload(array $overrides = []): array
{
    return array_merge([
        'firstname' => 'Awa',
        'lastname' => 'Diop',
        'email' => 'awa@example.com',
        'phone' => '+221771234567',
        'school_name' => 'Ecole Dakar',
        'school_email' => 'contact@ecoledakar.test',
        'school_phone' => '+221781234567',
        'ninea' => '1234567AB',
    ], $overrides);
}

it('allows a visitor to submit a valid registration request', function (): void {
    Notification::fake();

    $this->postJson('/api/v1/public/registration-requests', registrationPayload())
        ->assertCreated()
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('school_registration_requests', ['email' => 'awa@example.com', 'status' => 'PENDING']);
});

it('rejects invalid or duplicated registration data', function (): void {
    SchoolRegistrationRequest::query()->create(registrationPayload());

    $this->postJson('/api/v1/public/registration-requests', registrationPayload())->assertUnprocessable();
    $this->postJson('/api/v1/public/registration-requests', registrationPayload(['email' => 'new@example.com', 'ninea' => 'BAD']))->assertUnprocessable();
    $this->postJson('/api/v1/public/registration-requests', array_diff_key(registrationPayload(['email' => 'other@example.com', 'ninea' => '7654321CD']), ['school_name' => true]))->assertUnprocessable();
});

it('allows only super admins to list pending requests', function (): void {
    SchoolRegistrationRequest::query()->create(registrationPayload());

    $owner = User::factory()->create();
    $ownerSchool = School::factory()->create();
    \App\Models\UserRole::create(['user_id' => $owner->id, 'school_id' => $ownerSchool->id, 'role' => RoleEnum::Owner, 'is_active' => true, 'assigned_at' => now()]);

    $this->actingAs($owner)
        ->getJson('/api/v1/platform/registration-requests')
        ->assertForbidden();

    $superAdmin = User::factory()->create();
    \App\Models\UserRole::create(['user_id' => $superAdmin->id, 'school_id' => null, 'role' => RoleEnum::SuperAdmin, 'is_active' => true, 'assigned_at' => now()]);

    $this->actingAs($superAdmin)
        ->getJson('/api/v1/platform/registration-requests')
        ->assertOk()
        ->assertJsonPath('success', true);
});

it('approves a pending request and creates the onboarding records', function (): void {
    Notification::fake();
    SubscriptionPlan::query()->create([
        'name' => 'Starter',
        'price' => 0,
        'type' => 'MONTHLY',
        'duration' => 1,
        'max_students' => 200,
        'max_teachers' => 20,
        'features' => [],
    ]);
    $registration = SchoolRegistrationRequest::query()->create(registrationPayload());

    $superAdmin = User::factory()->create();
    \App\Models\UserRole::create(['user_id' => $superAdmin->id, 'school_id' => null, 'role' => RoleEnum::SuperAdmin, 'is_active' => true, 'assigned_at' => now()]);

    $this->actingAs($superAdmin)
        ->patchJson("/api/v1/platform/registration-requests/{$registration->id}/approve")
        ->assertOk();

    $this->assertDatabaseHas('users', ['email' => 'awa@example.com']);
    $user = User::where('email', 'awa@example.com')->first();
    $this->assertDatabaseHas('user_roles', ['user_id' => $user->id, 'role' => 'OWNER']);
    $this->assertDatabaseHas('personnel', ['user_id' => $user->id]);
    $this->assertDatabaseHas('schools', ['ninea' => '1234567AB']);
    $this->assertDatabaseCount('subscriptions', 1);
    Notification::assertSentTo($user, RegistrationApprovedNotification::class);
});

it('rejects a pending request with a reason', function (): void {
    $registration = SchoolRegistrationRequest::query()->create(registrationPayload());

    $superAdmin = User::factory()->create();
    \App\Models\UserRole::create(['user_id' => $superAdmin->id, 'school_id' => null, 'role' => RoleEnum::SuperAdmin, 'is_active' => true, 'assigned_at' => now()]);

    $this->actingAs($superAdmin)
        ->patchJson("/api/v1/platform/registration-requests/{$registration->id}/reject", ['rejection_reason' => 'Documents incomplets'])
        ->assertOk();

    $this->assertDatabaseHas('school_registration_requests', ['id' => $registration->id, 'status' => 'REJECTED']);
});
