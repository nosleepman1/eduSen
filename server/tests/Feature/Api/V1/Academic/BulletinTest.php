<?php

use App\Enums\RoleEnum;
use App\Models\AcademicPeriod;
use App\Models\Bulletin;
use App\Models\Classroom;
use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\Evaluation;
use App\Models\Grade;
use App\Models\Personnel;
use App\Models\Teacher;
use App\Models\TeachingAssignment;

function bulletinReadyFor(\App\Models\User $owner, float $gradeValue = 15): array
{
    $year = \App\Models\AcademicYear::factory()->create(['school_id' => $owner->test_school->id, 'status' => 'ACTIVE']);
    $level = \App\Models\Level::factory()->create(['school_id' => $owner->test_school->id, 'academic_year_id' => $year->id]);
    $classroom = Classroom::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $year->id,
        'level_id' => $level->id,
        'status' => 'ACTIVE',
    ]);
    $period = AcademicPeriod::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $year->id,
        'status' => 'ACTIVE',
    ]);
    $curriculum = Curriculum::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $year->id,
        'level_id' => $level->id,
        'specialization_id' => $classroom->specialization_id,
        'coefficient' => 4,
    ]);
    $personnel = Personnel::factory()->create(['school_id' => $owner->test_school->id]);
    $teacher = Teacher::factory()->create(['personnel_id' => $personnel->id]);
    $assignment = TeachingAssignment::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $year->id,
        'classroom_id' => $classroom->id,
        'curriculum_id' => $curriculum->id,
        'teacher_id' => $teacher->id,
    ]);
    $evaluation = Evaluation::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $year->id,
        'academic_period_id' => $period->id,
        'teaching_assignment_id' => $assignment->id,
        'coefficient' => 1,
        'status' => 'CLOSED',
    ]);
    $enrollment = Enrollment::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $year->id,
        'classroom_id' => $classroom->id,
    ]);
    Grade::factory()->create([
        'school_id' => $owner->test_school->id,
        'evaluation_id' => $evaluation->id,
        'enrollment_id' => $enrollment->id,
        'status' => 'GRADED',
        'value' => $gradeValue,
    ]);

    return [$classroom, $period, $enrollment, $evaluation, $assignment];
}

it('generates a bulletin with a general average, mention and a line per subject', function () {
    $owner = ownerWithActiveSubscription();
    [, $period, $enrollment] = bulletinReadyFor($owner, 16);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/bulletins/generate', [
            'enrollment_id' => $enrollment->id,
            'academic_period_id' => $period->id,
        ]);

    $response->assertCreated();
    expect((float) $response->json('data.general_average'))->toBe(16.0);
    expect($response->json('data.mention'))->toBe('Très Bien');
    expect($response->json('data.lines'))->toHaveCount(1);
    expect($response->json('data.class_rank'))->toBe(1);
    expect($response->json('data.total_students'))->toBe(1);
});

it('refuses generating a bulletin while an evaluation of the period is still open (RB-004)', function () {
    $owner = ownerWithActiveSubscription();
    [, $period, $enrollment, $evaluation] = bulletinReadyFor($owner);
    $evaluation->update(['status' => 'OPEN']);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/bulletins/generate', [
            'enrollment_id' => $enrollment->id,
            'academic_period_id' => $period->id,
        ]);

    $response->assertStatus(422);
});

it('refuses generating a bulletin while the student has a Pending grade (RB-005)', function () {
    $owner = ownerWithActiveSubscription();
    [, $period, $enrollment, $evaluation, $assignment] = bulletinReadyFor($owner);
    Grade::factory()->create([
        'school_id' => $owner->test_school->id,
        'evaluation_id' => Evaluation::factory()->create([
            'school_id' => $owner->test_school->id,
            'academic_year_id' => $assignment->academic_year_id,
            'academic_period_id' => $period->id,
            'teaching_assignment_id' => $assignment->id,
            'status' => 'CLOSED',
        ])->id,
        'enrollment_id' => $enrollment->id,
        'status' => 'PENDING',
    ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/bulletins/generate', [
            'enrollment_id' => $enrollment->id,
            'academic_period_id' => $period->id,
        ]);

    $response->assertStatus(422);
});

it('refuses a duplicate bulletin for the same enrollment and period (RB-003)', function () {
    $owner = ownerWithActiveSubscription();
    [, $period, $enrollment] = bulletinReadyFor($owner);
    Bulletin::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_period_id' => $period->id,
        'enrollment_id' => $enrollment->id,
    ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/bulletins/generate', [
            'enrollment_id' => $enrollment->id,
            'academic_period_id' => $period->id,
        ]);

    $response->assertStatus(422);
});

it('ranks the higher-average student first among classroom peers', function () {
    $owner = ownerWithActiveSubscription();
    [$classroom, $period, $enrollmentLow, $evaluation, $assignment] = bulletinReadyFor($owner, 10);

    $enrollmentHigh = Enrollment::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $assignment->academic_year_id,
        'classroom_id' => $classroom->id,
    ]);
    Grade::factory()->create([
        'school_id' => $owner->test_school->id,
        'evaluation_id' => $evaluation->id,
        'enrollment_id' => $enrollmentHigh->id,
        'status' => 'GRADED',
        'value' => 18,
    ]);

    $responseHigh = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/bulletins/generate', [
            'enrollment_id' => $enrollmentHigh->id,
            'academic_period_id' => $period->id,
        ]);
    $responseLow = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/bulletins/generate', [
            'enrollment_id' => $enrollmentLow->id,
            'academic_period_id' => $period->id,
        ]);

    expect($responseHigh->json('data.class_rank'))->toBe(1);
    expect($responseLow->json('data.class_rank'))->toBe(2);
    expect($responseHigh->json('data.total_students'))->toBe(2);
});

it('generates bulletins for a whole classroom via the queued job', function () {
    $owner = ownerWithActiveSubscription();
    [$classroom, $period] = bulletinReadyFor($owner, 15);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/bulletins/generate-class', [
            'classroom_id' => $classroom->id,
            'academic_period_id' => $period->id,
        ]);

    $response->assertStatus(202);
    $this->assertDatabaseHas('bulletins', ['academic_period_id' => $period->id]);
});

it('follows the Draft -> Validated -> Published -> Locked lifecycle', function () {
    $owner = ownerWithActiveSubscription();
    $bulletin = Bulletin::factory()->create(['school_id' => $owner->test_school->id, 'status' => 'DRAFT']);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/bulletins/{$bulletin->id}/validate", ['class_council_comment' => 'Bon trimestre.'])
        ->assertOk();
    $this->assertDatabaseHas('bulletins', ['id' => $bulletin->id, 'status' => 'VALIDATED', 'class_council_comment' => 'Bon trimestre.']);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/bulletins/{$bulletin->id}/publish")
        ->assertOk();
    $this->assertDatabaseHas('bulletins', ['id' => $bulletin->id, 'status' => 'PUBLISHED']);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/bulletins/{$bulletin->id}/lock")
        ->assertOk();
    $this->assertDatabaseHas('bulletins', ['id' => $bulletin->id, 'status' => 'LOCKED']);
});

it('refuses to modify a locked bulletin (RB-008)', function () {
    $owner = ownerWithActiveSubscription();
    $bulletin = Bulletin::factory()->create(['school_id' => $owner->test_school->id, 'status' => 'LOCKED']);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/bulletins/{$bulletin->id}/validate")
        ->assertStatus(422);
});

it('archives a locked bulletin and restores it back to locked', function () {
    $owner = ownerWithActiveSubscription();
    $bulletin = Bulletin::factory()->create(['school_id' => $owner->test_school->id, 'status' => 'LOCKED']);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->deleteJson("/api/v1/academic/bulletins/{$bulletin->id}")
        ->assertOk();

    $this->assertSoftDeleted('bulletins', ['id' => $bulletin->id]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/bulletins/{$bulletin->id}/restore")
        ->assertOk();

    $this->assertDatabaseHas('bulletins', ['id' => $bulletin->id, 'status' => 'LOCKED', 'deleted_at' => null]);
});

it('computes a classroom summary from generated bulletins', function () {
    $owner = ownerWithActiveSubscription();
    [$classroom, $period, $enrollment] = bulletinReadyFor($owner, 16);
    Bulletin::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_period_id' => $period->id,
        'enrollment_id' => $enrollment->id,
        'general_average' => 16,
    ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->getJson("/api/v1/academic/classrooms/{$classroom->id}/bulletin-summary?academic_period_id={$period->id}");

    $response->assertOk();
    expect((float) $response->json('data.class_average'))->toBe(16.0);
    expect($response->json('data.total_bulletins'))->toBe(1);
});

it('denies a role without the bulletin.manage permission', function () {
    $owner = ownerWithActiveSubscription();
    $secretary = staffMember($owner->test_school, RoleEnum::Secretary);
    [, $period, $enrollment] = bulletinReadyFor($owner);

    $response = $this->withHeader('Authorization', 'Bearer ' . $secretary->test_token)
        ->postJson('/api/v1/academic/bulletins/generate', [
            'enrollment_id' => $enrollment->id,
            'academic_period_id' => $period->id,
        ]);

    $response->assertStatus(403);
});

it('isolates bulletins between schools', function () {
    $ownerA = ownerWithActiveSubscription();
    $ownerB = ownerWithActiveSubscription();

    $bulletinB = Bulletin::factory()->create(['school_id' => $ownerB->test_school->id]);

    $this->withHeader('Authorization', 'Bearer ' . $ownerA->test_token)
        ->getJson("/api/v1/academic/bulletins/{$bulletinB->id}")
        ->assertStatus(403);
});
