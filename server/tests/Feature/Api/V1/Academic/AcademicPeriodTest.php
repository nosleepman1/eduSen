<?php

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;

it('creates an academic period within the year range', function () {
    $owner = ownerWithActiveSubscription();
    $year = AcademicYear::factory()->create([
        'school_id' => $owner->test_school->id,
        'start_date' => '2026-10-01',
        'end_date' => '2027-07-31',
    ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/periods', [
            'academic_year_id' => $year->id,
            'name' => 'Trimestre 1',
            'start_date' => '2026-10-01',
            'end_date' => '2026-12-20',
        ]);

    $response->assertCreated();
    $this->assertDatabaseHas('academic_periods', ['academic_year_id' => $year->id, 'name' => 'Trimestre 1']);
});

it('refuses a period whose dates fall outside its academic year', function () {
    $owner = ownerWithActiveSubscription();
    $year = AcademicYear::factory()->create([
        'school_id' => $owner->test_school->id,
        'start_date' => '2026-10-01',
        'end_date' => '2027-07-31',
    ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/periods', [
            'academic_year_id' => $year->id,
            'name' => 'Hors bornes',
            'start_date' => '2026-09-01',
            'end_date' => '2026-12-20',
        ]);

    $response->assertStatus(422);
    expect($response->json('code'))->toBe('ACADEMIC_PERIOD_OUT_OF_RANGE');
});

it('refuses overlapping periods within the same year', function () {
    $owner = ownerWithActiveSubscription();
    $year = AcademicYear::factory()->create([
        'school_id' => $owner->test_school->id,
        'start_date' => '2026-10-01',
        'end_date' => '2027-07-31',
    ]);

    // Pinned name (not left to the factory's random pick): the request below
    // hardcodes "Trimestre 2" for the overlapping period, and the factory's
    // random name pool includes that exact string — an unpinned name would
    // occasionally collide and fail on the unrelated "name already taken"
    // validation rule instead of the overlap check this test targets.
    AcademicPeriod::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $year->id,
        'name' => 'Trimestre 1',
        'start_date' => '2026-10-01',
        'end_date' => '2026-12-20',
    ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/periods', [
            'academic_year_id' => $year->id,
            'name' => 'Trimestre 2',
            'start_date' => '2026-12-01',
            'end_date' => '2027-03-01',
        ]);

    $response->assertStatus(422);
    expect($response->json('code'))->toBe('ACADEMIC_PERIOD_OVERLAP');
});

it('activating a period closes the previously active period of the same year', function () {
    $owner = ownerWithActiveSubscription();
    $year = AcademicYear::factory()->create([
        'school_id' => $owner->test_school->id,
        'start_date' => '2026-10-01',
        'end_date' => '2027-07-31',
    ]);

    $current = AcademicPeriod::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $year->id,
        'status' => 'ACTIVE',
        'start_date' => '2026-10-01',
        'end_date' => '2026-12-20',
    ]);

    $next = AcademicPeriod::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $year->id,
        'status' => 'DRAFT',
        'start_date' => '2027-01-05',
        'end_date' => '2027-03-20',
    ]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/periods/{$next->id}/activate")
        ->assertOk()
        ->assertJsonPath('data.status', 'ACTIVE');

    expect($current->fresh()->status->value)->toBe('CLOSED');
});

it('generates default periods for a trimester year', function () {
    $owner = ownerWithActiveSubscription();
    $year = AcademicYear::factory()->create([
        'school_id' => $owner->test_school->id,
        'start_date' => '2026-10-01',
        'end_date' => '2027-07-31',
    ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson("/api/v1/academic/years/{$year->id}/periods/generate", ['mode' => 'TRIMESTER']);

    $response->assertCreated();
    expect($response->json('data'))->toHaveCount(3);
    $this->assertDatabaseCount('academic_periods', 3);
});

it('blocks modification of a closed academic period', function () {
    $owner = ownerWithActiveSubscription();
    $year = AcademicYear::factory()->create(['school_id' => $owner->test_school->id]);
    $period = AcademicPeriod::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $year->id,
        'status' => 'CLOSED',
    ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->putJson("/api/v1/academic/periods/{$period->id}", ['name' => 'Nouveau nom']);

    $response->assertStatus(422);
    expect($response->json('code'))->toBe('ACADEMIC_PERIOD_CLOSED');
});

it('refuses a partial update that pushes start_date past the existing end_date', function () {
    $owner = ownerWithActiveSubscription();
    $year = AcademicYear::factory()->create([
        'school_id' => $owner->test_school->id,
        'start_date' => '2026-10-01',
        'end_date' => '2027-07-31',
    ]);
    $period = AcademicPeriod::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $year->id,
        'status' => 'DRAFT',
        'start_date' => '2026-10-01',
        'end_date' => '2026-12-20',
    ]);

    // Only start_date is sent — end_date's "after:start_date" FormRequest rule
    // never runs for an absent field, so the Service must catch this itself.
    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->putJson("/api/v1/academic/periods/{$period->id}", ['start_date' => '2027-01-01']);

    $response->assertStatus(422);
    expect($period->fresh()->start_date->toDateString())->toBe('2026-10-01');
});
