<?php

use App\Enums\RoleEnum;
use App\Models\Personnel;
use App\Models\UserRole;

it('lists and updates personnel', function () {
    $owner = ownerWithActiveSubscription();
    $personnel = Personnel::factory()->create(['school_id' => $owner->test_school->id]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->getJson('/api/v1/personnel')
        ->assertOk();

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->putJson("/api/v1/personnel/{$personnel->id}", ['department' => 'Pédagogie'])
        ->assertOk()
        ->assertJsonPath('data.department', 'Pédagogie');
});

it('updates the roles of a personnel member', function () {
    $owner = ownerWithActiveSubscription();
    $personnel = Personnel::factory()->create(['school_id' => $owner->test_school->id]);
    UserRole::create([
        'user_id' => $personnel->user_id,
        'school_id' => $owner->test_school->id,
        'role' => RoleEnum::Teacher,
        'is_active' => true,
        'assigned_at' => now(),
    ]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/personnel/{$personnel->id}/roles", ['roles' => ['SUPERVISOR']])
        ->assertOk();

    expect(UserRole::where('user_id', $personnel->user_id)->where('role', 'TEACHER')->first()->is_active)->toBeFalse();
    expect(UserRole::where('user_id', $personnel->user_id)->where('role', 'SUPERVISOR')->first()->is_active)->toBeTrue();
});

it('archiving a personnel member deactivates their roles for the school', function () {
    $owner = ownerWithActiveSubscription();
    $personnel = Personnel::factory()->create(['school_id' => $owner->test_school->id]);
    UserRole::create([
        'user_id' => $personnel->user_id,
        'school_id' => $owner->test_school->id,
        'role' => RoleEnum::Secretary,
        'is_active' => true,
        'assigned_at' => now(),
    ]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->deleteJson("/api/v1/personnel/{$personnel->id}")
        ->assertOk();

    $this->assertSoftDeleted('personnel', ['id' => $personnel->id]);
    expect(UserRole::where('user_id', $personnel->user_id)->where('role', 'SECRETARY')->first()->is_active)->toBeFalse();

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/personnel/{$personnel->id}/restore")
        ->assertOk();

    expect(UserRole::where('user_id', $personnel->user_id)->where('role', 'SECRETARY')->first()->is_active)->toBeTrue();
});

it('isolates personnel between schools', function () {
    $ownerA = ownerWithActiveSubscription();
    $ownerB = ownerWithActiveSubscription();

    $personnelB = Personnel::factory()->create(['school_id' => $ownerB->test_school->id]);

    $this->withHeader('Authorization', 'Bearer ' . $ownerA->test_token)
        ->getJson("/api/v1/personnel/{$personnelB->id}")
        ->assertStatus(403);
});
