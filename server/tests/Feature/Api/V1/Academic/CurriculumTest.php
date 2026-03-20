<?php

use App\Enums\RoleEnum;
use App\Models\AcademicYear;
use App\Models\Curriculum;
use App\Models\Level;
use App\Models\Specialization;
use App\Models\Subject;

it('creates a curriculum line via the API', function () {
    $owner = ownerWithActiveSubscription();
    $level = Level::factory()->create(['school_id' => $owner->test_school->id]);
    $subject = Subject::factory()->create(['school_id' => $owner->test_school->id]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/curriculums', [
            'level_id' => $level->id,
            'subject_id' => $subject->id,
            'coefficient' => 5,
            'weekly_hours' => 6,
        ]);

    $response->assertCreated();
    $this->assertDatabaseHas('curriculums', [
        'school_id' => $owner->test_school->id,
        'level_id' => $level->id,
        'subject_id' => $subject->id,
    ]);
});

it('refuses a duplicate subject for the same level and specialization (RCU-005)', function () {
    $owner = ownerWithActiveSubscription();
    $level = Level::factory()->create(['school_id' => $owner->test_school->id]);
    $subject = Subject::factory()->create(['school_id' => $owner->test_school->id]);
    Curriculum::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $level->academic_year_id,
        'level_id' => $level->id,
        'specialization_id' => null,
        'subject_id' => $subject->id,
    ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/curriculums', [
            'level_id' => $level->id,
            'subject_id' => $subject->id,
            'coefficient' => 3,
            'weekly_hours' => 4,
        ]);

    $response->assertStatus(422);
    expect($response->json('code'))->toBe('CURRICULUM_SUBJECT_CONFLICT');
});

it('allows the same subject under different specializations of the same level', function () {
    $owner = ownerWithActiveSubscription();
    $level = Level::factory()->create(['school_id' => $owner->test_school->id]);
    $subject = Subject::factory()->create(['school_id' => $owner->test_school->id]);
    $specializationS = Specialization::factory()->create(['school_id' => $owner->test_school->id, 'code' => 'S']);
    $specializationL = Specialization::factory()->create(['school_id' => $owner->test_school->id, 'code' => 'L']);

    Curriculum::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $level->academic_year_id,
        'level_id' => $level->id,
        'specialization_id' => $specializationS->id,
        'subject_id' => $subject->id,
    ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/curriculums', [
            'level_id' => $level->id,
            'specialization_id' => $specializationL->id,
            'subject_id' => $subject->id,
            'coefficient' => 7,
            'weekly_hours' => 8,
        ]);

    $response->assertCreated();
});

it('refuses a non-positive coefficient (RCU-006)', function () {
    $owner = ownerWithActiveSubscription();
    $level = Level::factory()->create(['school_id' => $owner->test_school->id]);
    $subject = Subject::factory()->create(['school_id' => $owner->test_school->id]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/curriculums', [
            'level_id' => $level->id,
            'subject_id' => $subject->id,
            'coefficient' => 0,
            'weekly_hours' => 4,
        ]);

    $response->assertStatus(422);
});

it('refuses a non-positive weekly_hours (RCU-007)', function () {
    $owner = ownerWithActiveSubscription();
    $level = Level::factory()->create(['school_id' => $owner->test_school->id]);
    $subject = Subject::factory()->create(['school_id' => $owner->test_school->id]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/curriculums', [
            'level_id' => $level->id,
            'subject_id' => $subject->id,
            'coefficient' => 4,
            'weekly_hours' => 0,
        ]);

    $response->assertStatus(422);
});

it('archives and restores a curriculum line', function () {
    $owner = ownerWithActiveSubscription();
    $curriculum = Curriculum::factory()->create(['school_id' => $owner->test_school->id]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->deleteJson("/api/v1/academic/curriculums/{$curriculum->id}")
        ->assertOk();

    $this->assertSoftDeleted('curriculums', ['id' => $curriculum->id]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/curriculums/{$curriculum->id}/restore")
        ->assertOk();
});

it('duplicates a curriculum to a new academic year by matching level codes', function () {
    $owner = ownerWithActiveSubscription();
    $schoolId = $owner->test_school->id;

    $yearA = AcademicYear::factory()->create(['school_id' => $schoolId, 'start_date' => '2026-10-01', 'end_date' => '2027-07-31']);
    $yearB = AcademicYear::factory()->create(['school_id' => $schoolId, 'start_date' => '2027-10-01', 'end_date' => '2028-07-31']);

    $levelA = Level::factory()->create(['school_id' => $schoolId, 'academic_year_id' => $yearA->id, 'code' => 'TLE']);
    $levelB = Level::factory()->create(['school_id' => $schoolId, 'academic_year_id' => $yearB->id, 'code' => 'TLE']);

    $subject = Subject::factory()->create(['school_id' => $schoolId]);
    Curriculum::factory()->create([
        'school_id' => $schoolId,
        'academic_year_id' => $yearA->id,
        'level_id' => $levelA->id,
        'subject_id' => $subject->id,
        'coefficient' => 5,
        'weekly_hours' => 6,
    ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/curriculums/duplicate', [
            'from_academic_year_id' => $yearA->id,
            'to_academic_year_id' => $yearB->id,
        ]);

    $response->assertOk();
    expect($response->json('data.created'))->toHaveCount(1);
    $this->assertDatabaseHas('curriculums', [
        'academic_year_id' => $yearB->id,
        'level_id' => $levelB->id,
        'subject_id' => $subject->id,
    ]);
});

it('skips duplication for a level with no matching code in the target year', function () {
    $owner = ownerWithActiveSubscription();
    $schoolId = $owner->test_school->id;

    $yearA = AcademicYear::factory()->create(['school_id' => $schoolId, 'start_date' => '2026-10-01', 'end_date' => '2027-07-31']);
    $yearB = AcademicYear::factory()->create(['school_id' => $schoolId, 'start_date' => '2027-10-01', 'end_date' => '2028-07-31']);

    $levelA = Level::factory()->create(['school_id' => $schoolId, 'academic_year_id' => $yearA->id, 'code' => 'TLE']);
    // yearB has no level with a matching code at all.

    $subject = Subject::factory()->create(['school_id' => $schoolId]);
    Curriculum::factory()->create([
        'school_id' => $schoolId,
        'academic_year_id' => $yearA->id,
        'level_id' => $levelA->id,
        'subject_id' => $subject->id,
    ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/curriculums/duplicate', [
            'from_academic_year_id' => $yearA->id,
            'to_academic_year_id' => $yearB->id,
        ]);

    $response->assertOk();
    expect($response->json('data.created'))->toHaveCount(0);
    expect($response->json('data.skipped'))->toHaveCount(1);
    expect($response->json('data.skipped.0.reason'))->toBe('no_matching_level');
});

it('skips a duplication line whose source level was archived, instead of failing the whole request', function () {
    $owner = ownerWithActiveSubscription();
    $schoolId = $owner->test_school->id;

    $yearA = AcademicYear::factory()->create(['school_id' => $schoolId, 'start_date' => '2026-10-01', 'end_date' => '2027-07-31']);
    $yearB = AcademicYear::factory()->create(['school_id' => $schoolId, 'start_date' => '2027-10-01', 'end_date' => '2028-07-31']);

    $levelA = Level::factory()->create(['school_id' => $schoolId, 'academic_year_id' => $yearA->id, 'code' => 'TLE']);
    $subject = Subject::factory()->create(['school_id' => $schoolId]);
    Curriculum::factory()->create([
        'school_id' => $schoolId,
        'academic_year_id' => $yearA->id,
        'level_id' => $levelA->id,
        'subject_id' => $subject->id,
    ]);

    // The source level is archived after the curriculum line was created —
    // Curriculum::level() respects SoftDeletingScope, so it now resolves to
    // null for that row.
    $levelA->delete();

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/curriculums/duplicate', [
            'from_academic_year_id' => $yearA->id,
            'to_academic_year_id' => $yearB->id,
        ]);

    $response->assertOk();
    expect($response->json('data.created'))->toHaveCount(0);
    expect($response->json('data.skipped'))->toHaveCount(1);
    expect($response->json('data.skipped.0.reason'))->toBe('source_level_archived');
});

it('denies a role without the curriculum.manage permission', function () {
    $owner = ownerWithActiveSubscription();
    $teacher = staffMember($owner->test_school, RoleEnum::Teacher);
    $level = Level::factory()->create(['school_id' => $owner->test_school->id]);
    $subject = Subject::factory()->create(['school_id' => $owner->test_school->id]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $teacher->test_token)
        ->postJson('/api/v1/academic/curriculums', [
            'level_id' => $level->id,
            'subject_id' => $subject->id,
            'coefficient' => 4,
            'weekly_hours' => 4,
        ]);

    $response->assertStatus(403);
});

it('isolates curriculum lines between schools', function () {
    $ownerA = ownerWithActiveSubscription();
    $ownerB = ownerWithActiveSubscription();

    $curriculumB = Curriculum::factory()->create(['school_id' => $ownerB->test_school->id]);

    $this->withHeader('Authorization', 'Bearer ' . $ownerA->test_token)
        ->getJson("/api/v1/academic/curriculums/{$curriculumB->id}")
        ->assertStatus(403);
});
