<?php

use App\Enums\RoleEnum;
use App\Models\AcademicPeriod;
use App\Models\Enrollment;
use App\Models\Evaluation;
use App\Models\Grade;
use App\Models\TeachingAssignment;

function gradeReadyFor(\App\Models\User $owner): array
{
    $assignment = TeachingAssignment::factory()->create(['school_id' => $owner->test_school->id]);
    $period = AcademicPeriod::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $assignment->academic_year_id,
        'status' => 'ACTIVE',
    ]);
    $evaluation = Evaluation::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $assignment->academic_year_id,
        'academic_period_id' => $period->id,
        'teaching_assignment_id' => $assignment->id,
        'max_score' => 20,
    ]);
    $enrollment = Enrollment::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $assignment->academic_year_id,
        'classroom_id' => $assignment->classroom_id,
    ]);

    return [$evaluation, $enrollment, $assignment, $period];
}

it('creates a Graded grade within the evaluation max_score', function () {
    $owner = ownerWithActiveSubscription();
    [$evaluation, $enrollment] = gradeReadyFor($owner);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/grades', [
            'evaluation_id' => $evaluation->id,
            'enrollment_id' => $enrollment->id,
            'status' => 'GRADED',
            'value' => 15.5,
        ]);

    $response->assertCreated();
    $this->assertDatabaseHas('grades', [
        'evaluation_id' => $evaluation->id,
        'enrollment_id' => $enrollment->id,
        'status' => 'GRADED',
        'value' => 15.5,
    ]);
});

it('refuses a value above the evaluation max_score (RG-004)', function () {
    $owner = ownerWithActiveSubscription();
    [$evaluation, $enrollment] = gradeReadyFor($owner);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/grades', [
            'evaluation_id' => $evaluation->id,
            'enrollment_id' => $enrollment->id,
            'status' => 'GRADED',
            'value' => 25,
        ]);

    $response->assertStatus(422);
});

it('refuses a duplicate grade for the same evaluation and enrollment (RG-003)', function () {
    $owner = ownerWithActiveSubscription();
    [$evaluation, $enrollment] = gradeReadyFor($owner);
    Grade::factory()->create([
        'school_id' => $owner->test_school->id,
        'evaluation_id' => $evaluation->id,
        'enrollment_id' => $enrollment->id,
    ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/grades', [
            'evaluation_id' => $evaluation->id,
            'enrollment_id' => $enrollment->id,
        ]);

    $response->assertStatus(422);
});

it('refuses a grade for a student not enrolled in the evaluation classroom (RG-007)', function () {
    $owner = ownerWithActiveSubscription();
    [$evaluation] = gradeReadyFor($owner);
    $otherEnrollment = Enrollment::factory()->create(['school_id' => $owner->test_school->id]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/grades', [
            'evaluation_id' => $evaluation->id,
            'enrollment_id' => $otherEnrollment->id,
        ]);

    $response->assertStatus(422);
});

it('refuses modifying a grade whose evaluation is locked (RG-005)', function () {
    $owner = ownerWithActiveSubscription();
    [$evaluation, $enrollment] = gradeReadyFor($owner);
    $evaluation->update(['status' => 'LOCKED']);
    $grade = Grade::factory()->create([
        'school_id' => $owner->test_school->id,
        'evaluation_id' => $evaluation->id,
        'enrollment_id' => $enrollment->id,
    ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->putJson("/api/v1/academic/grades/{$grade->id}", ['status' => 'GRADED', 'value' => 10]);

    $response->assertStatus(422);
});

it('forces value to 0 when marking a grade Absent (RG-010)', function () {
    $owner = ownerWithActiveSubscription();
    [$evaluation, $enrollment] = gradeReadyFor($owner);
    $grade = Grade::factory()->create([
        'school_id' => $owner->test_school->id,
        'evaluation_id' => $evaluation->id,
        'enrollment_id' => $enrollment->id,
    ]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->putJson("/api/v1/academic/grades/{$grade->id}", ['status' => 'ABSENT'])
        ->assertOk();

    $this->assertDatabaseHas('grades', ['id' => $grade->id, 'status' => 'ABSENT', 'value' => 0]);
});

it('clears the value when marking a grade Dispensed (RG-009)', function () {
    $owner = ownerWithActiveSubscription();
    [$evaluation, $enrollment] = gradeReadyFor($owner);
    $grade = Grade::factory()->create([
        'school_id' => $owner->test_school->id,
        'evaluation_id' => $evaluation->id,
        'enrollment_id' => $enrollment->id,
        'status' => 'GRADED',
        'value' => 12,
    ]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->putJson("/api/v1/academic/grades/{$grade->id}", ['status' => 'DISPENSED'])
        ->assertOk();

    $this->assertDatabaseHas('grades', ['id' => $grade->id, 'status' => 'DISPENSED', 'value' => null]);
});

it('generates Pending grades for every active enrollment when the evaluation opens', function () {
    $owner = ownerWithActiveSubscription();
    [$evaluation, $enrollment] = gradeReadyFor($owner);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/evaluations/{$evaluation->id}/open");

    $response->assertOk();
    $this->assertDatabaseHas('grades', [
        'evaluation_id' => $evaluation->id,
        'enrollment_id' => $enrollment->id,
        'status' => 'PENDING',
    ]);
});

it('computes subject and general averages, excluding Dispensed and Pending grades', function () {
    $owner = ownerWithActiveSubscription();
    [$evaluation1, $enrollment, $assignment, $period] = gradeReadyFor($owner);
    $evaluation1->update(['coefficient' => 1]);
    Grade::factory()->create([
        'school_id' => $owner->test_school->id,
        'evaluation_id' => $evaluation1->id,
        'enrollment_id' => $enrollment->id,
        'status' => 'GRADED',
        'value' => 16,
    ]);

    $evaluation2 = Evaluation::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $assignment->academic_year_id,
        'academic_period_id' => $period->id,
        'teaching_assignment_id' => $assignment->id,
        'type' => 'EXAMEN',
        'coefficient' => 2,
        'max_score' => 20,
    ]);
    Grade::factory()->create([
        'school_id' => $owner->test_school->id,
        'evaluation_id' => $evaluation2->id,
        'enrollment_id' => $enrollment->id,
        'status' => 'GRADED',
        'value' => 10,
    ]);
    // A Dispensed grade on a third evaluation must not affect the average.
    $evaluation3 = Evaluation::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $assignment->academic_year_id,
        'academic_period_id' => $period->id,
        'teaching_assignment_id' => $assignment->id,
    ]);
    Grade::factory()->create([
        'school_id' => $owner->test_school->id,
        'evaluation_id' => $evaluation3->id,
        'enrollment_id' => $enrollment->id,
        'status' => 'DISPENSED',
        'value' => null,
    ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->getJson("/api/v1/academic/enrollments/{$enrollment->id}/averages?academic_period_id={$period->id}");

    $response->assertOk();
    // (16*1 + 10*2) / (1+2) = 12
    expect((float) $response->json('data.subjects.0.average'))->toBe(12.0);
    expect($response->json('data.has_pending'))->toBeFalse();
});

it('archives and restores a grade', function () {
    $owner = ownerWithActiveSubscription();
    $grade = Grade::factory()->create(['school_id' => $owner->test_school->id]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->deleteJson("/api/v1/academic/grades/{$grade->id}")
        ->assertOk();

    $this->assertSoftDeleted('grades', ['id' => $grade->id]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/grades/{$grade->id}/restore")
        ->assertOk();
});

it('denies a role without the grade.manage permission', function () {
    $owner = ownerWithActiveSubscription();
    $secretary = staffMember($owner->test_school, RoleEnum::Secretary);
    [$evaluation, $enrollment] = gradeReadyFor($owner);

    $response = $this->withHeader('Authorization', 'Bearer ' . $secretary->test_token)
        ->postJson('/api/v1/academic/grades', [
            'evaluation_id' => $evaluation->id,
            'enrollment_id' => $enrollment->id,
        ]);

    $response->assertStatus(403);
});

it('isolates grades between schools', function () {
    $ownerA = ownerWithActiveSubscription();
    $ownerB = ownerWithActiveSubscription();

    $gradeB = Grade::factory()->create(['school_id' => $ownerB->test_school->id]);

    $this->withHeader('Authorization', 'Bearer ' . $ownerA->test_token)
        ->getJson("/api/v1/academic/grades/{$gradeB->id}")
        ->assertStatus(403);
});
