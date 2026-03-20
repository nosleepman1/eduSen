<?php

use App\Enums\RoleEnum;
use App\Models\Personnel;
use App\Models\Teacher;
use App\Models\UserRole;

function personnelWithTeacherRole(\App\Models\School $school): Personnel
{
    $personnel = Personnel::factory()->create(['school_id' => $school->id]);

    UserRole::create([
        'user_id' => $personnel->user_id,
        'school_id' => $school->id,
        'role' => RoleEnum::Teacher,
        'is_active' => true,
        'assigned_at' => now(),
    ]);

    return $personnel;
}

it('creates a teacher profile for personnel holding the TEACHER role', function () {
    $owner = ownerWithActiveSubscription();
    $personnel = personnelWithTeacherRole($owner->test_school);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/teachers', [
            'personnel_id' => $personnel->id,
            'speciality' => 'Mathématiques',
        ]);

    $response->assertCreated();
    $this->assertDatabaseHas('teachers', ['personnel_id' => $personnel->id, 'speciality' => 'Mathématiques']);
});

it('refuses a teacher profile for personnel without the TEACHER role', function () {
    $owner = ownerWithActiveSubscription();
    $personnel = Personnel::factory()->create(['school_id' => $owner->test_school->id]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/teachers', [
            'personnel_id' => $personnel->id,
            'speciality' => 'Mathématiques',
        ]);

    $response->assertStatus(422);
});

it('refuses a teacher profile once the subscription max_teachers limit is reached', function () {
    $owner = ownerWithActiveSubscription();
    $owner->test_school->activeSubscription->plan->update(['max_teachers' => 0]);

    $personnel = personnelWithTeacherRole($owner->test_school);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/teachers', [
            'personnel_id' => $personnel->id,
            'speciality' => 'Mathématiques',
        ]);

    $response->assertStatus(422);
});

it('updates and archives a teacher, cascading to the personnel record', function () {
    $owner = ownerWithActiveSubscription();
    $personnel = personnelWithTeacherRole($owner->test_school);
    $teacher = Teacher::factory()->create(['personnel_id' => $personnel->id]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->putJson("/api/v1/teachers/{$teacher->id}", ['speciality' => 'Physique'])
        ->assertOk()
        ->assertJsonPath('data.speciality', 'Physique');

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->deleteJson("/api/v1/teachers/{$teacher->id}")
        ->assertOk();

    $this->assertSoftDeleted('personnel', ['id' => $personnel->id]);

    // Archiving the teacher must revoke access, not just soft-delete the row —
    // otherwise the underlying user keeps an active TEACHER role after "archival".
    expect(Personnel::withTrashed()->find($personnel->id)->status->value)->toBe('SUSPENDED');
    expect(UserRole::where('user_id', $personnel->user_id)->where('role', 'TEACHER')->first()->is_active)->toBeFalse();

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/teachers/{$teacher->id}/restore")
        ->assertOk();

    $this->assertDatabaseHas('personnel', ['id' => $personnel->id, 'deleted_at' => null]);
    expect(Personnel::find($personnel->id)->status->value)->toBe('ACTIVE');
    expect(UserRole::where('user_id', $personnel->user_id)->where('role', 'TEACHER')->first()->is_active)->toBeTrue();
});
