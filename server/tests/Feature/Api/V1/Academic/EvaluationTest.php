<?php

use App\Enums\RoleEnum;
use App\Models\AcademicPeriod;
use App\Models\Evaluation;
use App\Models\TeachingAssignment;

function evaluationReadyFor(\App\Models\User $owner): array
{
    $assignment = TeachingAssignment::factory()->create(['school_id' => $owner->test_school->id]);
    $period = AcademicPeriod::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $assignment->academic_year_id,
        'status' => 'ACTIVE',
    ]);

    return [$period, $assignment];
}

it('creates a Devoir evaluation via the API', function () {
    $owner = ownerWithActiveSubscription();
    [$period, $assignment] = evaluationReadyFor($owner);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/evaluations', [
            'academic_period_id' => $period->id,
            'teaching_assignment_id' => $assignment->id,
            'type' => 'DEVOIR',
            'title' => 'Devoir 1',
            'evaluation_date' => $period->start_date->toDateString(),
            'max_score' => 20,
        ]);

    $response->assertCreated();
    expect($response->json('data.warning'))->toBeNull();
    $this->assertDatabaseHas('evaluations', [
        'academic_period_id' => $period->id,
        'teaching_assignment_id' => $assignment->id,
        'title' => 'Devoir 1',
        'status' => 'DRAFT',
    ]);
});

it('refuses creating an evaluation on a non-active period (REV-005)', function () {
    $owner = ownerWithActiveSubscription();
    [$period, $assignment] = evaluationReadyFor($owner);
    $period->update(['status' => 'DRAFT']);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/evaluations', [
            'academic_period_id' => $period->id,
            'teaching_assignment_id' => $assignment->id,
            'type' => 'DEVOIR',
            'title' => 'Devoir 1',
            'evaluation_date' => $period->start_date->toDateString(),
            'max_score' => 20,
        ]);

    $response->assertStatus(422);
});

it('refuses an evaluation date outside the academic period (REV-004)', function () {
    $owner = ownerWithActiveSubscription();
    [$period, $assignment] = evaluationReadyFor($owner);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/evaluations', [
            'academic_period_id' => $period->id,
            'teaching_assignment_id' => $assignment->id,
            'type' => 'DEVOIR',
            'title' => 'Devoir 1',
            'evaluation_date' => $period->end_date->copy()->addYear()->toDateString(),
            'max_score' => 20,
        ]);

    $response->assertStatus(422);
});

it('refuses a non-positive max_score (REV-006)', function () {
    $owner = ownerWithActiveSubscription();
    [$period, $assignment] = evaluationReadyFor($owner);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/evaluations', [
            'academic_period_id' => $period->id,
            'teaching_assignment_id' => $assignment->id,
            'type' => 'DEVOIR',
            'title' => 'Devoir 1',
            'evaluation_date' => $period->start_date->toDateString(),
            'max_score' => 0,
        ]);

    $response->assertStatus(422);
});

it('warns, but still allows, a second Examen for the same assignment and period', function () {
    $owner = ownerWithActiveSubscription();
    [$period, $assignment] = evaluationReadyFor($owner);
    Evaluation::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_period_id' => $period->id,
        'teaching_assignment_id' => $assignment->id,
        'type' => 'EXAMEN',
    ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/evaluations', [
            'academic_period_id' => $period->id,
            'teaching_assignment_id' => $assignment->id,
            'type' => 'EXAMEN',
            'title' => 'Examen final',
            'evaluation_date' => $period->start_date->toDateString(),
            'max_score' => 20,
        ]);

    $response->assertCreated();
    expect($response->json('data.warning'))->not->toBeNull();
});

it('follows the Draft -> Open -> Closed -> Locked lifecycle', function () {
    $owner = ownerWithActiveSubscription();
    $evaluation = Evaluation::factory()->create(['school_id' => $owner->test_school->id, 'status' => 'DRAFT']);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/evaluations/{$evaluation->id}/open")
        ->assertOk();
    $this->assertDatabaseHas('evaluations', ['id' => $evaluation->id, 'status' => 'OPEN']);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/evaluations/{$evaluation->id}/close")
        ->assertOk();
    $this->assertDatabaseHas('evaluations', ['id' => $evaluation->id, 'status' => 'CLOSED']);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/evaluations/{$evaluation->id}/lock")
        ->assertOk();
    $this->assertDatabaseHas('evaluations', ['id' => $evaluation->id, 'status' => 'LOCKED']);
});

it('refuses to modify a locked evaluation (REV-008)', function () {
    $owner = ownerWithActiveSubscription();
    $evaluation = Evaluation::factory()->create(['school_id' => $owner->test_school->id, 'status' => 'LOCKED']);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->putJson("/api/v1/academic/evaluations/{$evaluation->id}", ['title' => 'Nouveau titre'])
        ->assertStatus(422);
});

it('refuses opening an evaluation that is not in draft', function () {
    $owner = ownerWithActiveSubscription();
    $evaluation = Evaluation::factory()->create(['school_id' => $owner->test_school->id, 'status' => 'OPEN']);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/evaluations/{$evaluation->id}/open")
        ->assertStatus(422);
});

it('archives a locked evaluation and restores it back to locked', function () {
    $owner = ownerWithActiveSubscription();
    $evaluation = Evaluation::factory()->create(['school_id' => $owner->test_school->id, 'status' => 'LOCKED']);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->deleteJson("/api/v1/academic/evaluations/{$evaluation->id}")
        ->assertOk();

    $this->assertSoftDeleted('evaluations', ['id' => $evaluation->id]);
    $this->assertDatabaseHas('evaluations', ['id' => $evaluation->id, 'status' => 'LOCKED']);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/evaluations/{$evaluation->id}/restore")
        ->assertOk();

    $this->assertDatabaseHas('evaluations', ['id' => $evaluation->id, 'status' => 'LOCKED', 'deleted_at' => null]);
});

it('denies a role without the evaluation.manage permission', function () {
    $owner = ownerWithActiveSubscription();
    $secretary = staffMember($owner->test_school, RoleEnum::Secretary);
    [$period, $assignment] = evaluationReadyFor($owner);

    $response = $this->withHeader('Authorization', 'Bearer ' . $secretary->test_token)
        ->postJson('/api/v1/academic/evaluations', [
            'academic_period_id' => $period->id,
            'teaching_assignment_id' => $assignment->id,
            'type' => 'DEVOIR',
            'title' => 'Devoir 1',
            'evaluation_date' => $period->start_date->toDateString(),
            'max_score' => 20,
        ]);

    $response->assertStatus(403);
});

it('isolates evaluations between schools', function () {
    $ownerA = ownerWithActiveSubscription();
    $ownerB = ownerWithActiveSubscription();

    $evaluationB = Evaluation::factory()->create(['school_id' => $ownerB->test_school->id]);

    $this->withHeader('Authorization', 'Bearer ' . $ownerA->test_token)
        ->getJson("/api/v1/academic/evaluations/{$evaluationB->id}")
        ->assertStatus(403);
});
