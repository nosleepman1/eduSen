<?php

use App\Enums\RoleEnum;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Level;
use App\Models\Personnel;
use App\Models\Specialization;
use App\Models\Teacher;

it('creates a classroom via the API', function () {
    $owner = ownerWithActiveSubscription();
    $level = Level::factory()->create(['school_id' => $owner->test_school->id]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/classrooms', [
            'level_id' => $level->id,
            'name' => 'A',
            'capacity' => 40,
        ]);

    $response->assertCreated();
    $this->assertDatabaseHas('classrooms', [
        'school_id' => $owner->test_school->id,
        'level_id' => $level->id,
        'name' => 'A',
        'status' => 'DRAFT',
    ]);
});

it('auto-generates a code when none is provided', function () {
    $owner = ownerWithActiveSubscription();
    $level = Level::factory()->create(['school_id' => $owner->test_school->id, 'code' => 'L6']);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/classrooms', [
            'level_id' => $level->id,
            'name' => 'A',
            'capacity' => 40,
        ]);

    $response->assertCreated();
    expect($response->json('data.code'))->not->toBeNull();
});

it('builds the dynamic full_name from level, specialization and classroom name', function () {
    $owner = ownerWithActiveSubscription();
    $level = Level::factory()->create(['school_id' => $owner->test_school->id, 'name' => 'Terminale']);
    $specialization = Specialization::factory()->create(['school_id' => $owner->test_school->id, 'code' => 'S']);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/classrooms', [
            'level_id' => $level->id,
            'specialization_id' => $specialization->id,
            'name' => 'A',
            'capacity' => 40,
        ]);

    $response->assertCreated();
    expect($response->json('data.full_name'))->toBe('Terminale S A');
});

it('refuses a duplicate classroom name for the same level and specialization (RC-004)', function () {
    $owner = ownerWithActiveSubscription();
    $level = Level::factory()->create(['school_id' => $owner->test_school->id]);
    Classroom::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $level->academic_year_id,
        'level_id' => $level->id,
        'specialization_id' => null,
        'name' => 'A',
    ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/classrooms', [
            'level_id' => $level->id,
            'name' => 'A',
            'capacity' => 40,
        ]);

    $response->assertStatus(422);
    expect($response->json('code'))->toBe('CLASSROOM_NAME_CONFLICT');
});

it('allows the same classroom name under different specializations of the same level', function () {
    $owner = ownerWithActiveSubscription();
    $level = Level::factory()->create(['school_id' => $owner->test_school->id]);
    $specializationS = Specialization::factory()->create(['school_id' => $owner->test_school->id, 'code' => 'S']);
    $specializationL = Specialization::factory()->create(['school_id' => $owner->test_school->id, 'code' => 'L']);

    Classroom::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $level->academic_year_id,
        'level_id' => $level->id,
        'specialization_id' => $specializationS->id,
        'name' => 'A',
    ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/classrooms', [
            'level_id' => $level->id,
            'specialization_id' => $specializationL->id,
            'name' => 'A',
            'capacity' => 40,
        ]);

    $response->assertCreated();
});

it('refuses an end_date_override outside the academic year range (RC-009)', function () {
    $owner = ownerWithActiveSubscription();
    $year = AcademicYear::factory()->create([
        'school_id' => $owner->test_school->id,
        'start_date' => '2026-10-01',
        'end_date' => '2027-07-31',
    ]);
    $level = Level::factory()->create(['school_id' => $owner->test_school->id, 'academic_year_id' => $year->id]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/classrooms', [
            'level_id' => $level->id,
            'name' => 'A',
            'capacity' => 40,
            'end_date_override' => '2027-09-01',
        ]);

    $response->assertStatus(422);
    expect($response->json('code'))->toBe('CLASSROOM_END_DATE_OUT_OF_RANGE');
});

it('opens and closes a classroom', function () {
    $owner = ownerWithActiveSubscription();
    $classroom = Classroom::factory()->create(['school_id' => $owner->test_school->id]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/classrooms/{$classroom->id}/open")
        ->assertOk()
        ->assertJsonPath('data.status', 'ACTIVE');

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/classrooms/{$classroom->id}/close")
        ->assertOk()
        ->assertJsonPath('data.status', 'CLOSED');
});

it('archives and restores a classroom', function () {
    $owner = ownerWithActiveSubscription();
    $classroom = Classroom::factory()->create(['school_id' => $owner->test_school->id]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->deleteJson("/api/v1/academic/classrooms/{$classroom->id}")
        ->assertOk();

    $this->assertSoftDeleted('classrooms', ['id' => $classroom->id]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/classrooms/{$classroom->id}/restore")
        ->assertOk();
});

it('assigns a principal teacher who has a linked Teacher profile', function () {
    $owner = ownerWithActiveSubscription();
    $classroom = Classroom::factory()->create(['school_id' => $owner->test_school->id]);
    $personnel = Personnel::factory()->create(['school_id' => $owner->test_school->id]);
    Teacher::factory()->create(['personnel_id' => $personnel->id]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/classrooms/{$classroom->id}/principal-teacher", [
            'main_teacher_id' => $personnel->id,
        ]);

    $response->assertOk();
    expect($classroom->fresh()->main_teacher_id)->toBe($personnel->id);
});

it('refuses to assign a principal teacher who has no linked Teacher profile', function () {
    $owner = ownerWithActiveSubscription();
    $classroom = Classroom::factory()->create(['school_id' => $owner->test_school->id]);
    $personnel = Personnel::factory()->create(['school_id' => $owner->test_school->id]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/classrooms/{$classroom->id}/principal-teacher", [
            'main_teacher_id' => $personnel->id,
        ]);

    $response->assertStatus(422);
});

it('denies a role without the classroom.manage permission', function () {
    $owner = ownerWithActiveSubscription();
    $teacher = staffMember($owner->test_school, RoleEnum::Teacher);
    $level = Level::factory()->create(['school_id' => $owner->test_school->id]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $teacher->test_token)
        ->postJson('/api/v1/academic/classrooms', [
            'level_id' => $level->id,
            'name' => 'A',
            'capacity' => 40,
        ]);

    $response->assertStatus(403);
});

it('isolates classrooms between schools', function () {
    $ownerA = ownerWithActiveSubscription();
    $ownerB = ownerWithActiveSubscription();

    $classroomB = Classroom::factory()->create(['school_id' => $ownerB->test_school->id]);

    $this->withHeader('Authorization', 'Bearer ' . $ownerA->test_token)
        ->getJson("/api/v1/academic/classrooms/{$classroomB->id}")
        ->assertStatus(403);
});
