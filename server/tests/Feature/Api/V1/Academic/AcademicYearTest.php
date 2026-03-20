<?php

use App\Enums\RoleEnum;
use App\Models\AcademicYear;

it('creates an academic year via the API', function () {
    $owner = ownerWithActiveSubscription();

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/years', [
            'name' => '2026 - 2027',
            'start_date' => '2026-10-01',
            'end_date' => '2027-07-31',
        ]);

    $response->assertCreated();
    $response->assertJsonPath('data.status', 'DRAFT');
    $this->assertDatabaseHas('academic_years', ['school_id' => $owner->test_school->id, 'name' => '2026 - 2027']);
});

it('refuses overlapping academic years', function () {
    $owner = ownerWithActiveSubscription();
    AcademicYear::factory()->create([
        'school_id' => $owner->test_school->id,
        'start_date' => '2026-10-01',
        'end_date' => '2027-07-31',
    ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/years', [
            'name' => 'Chevauchement',
            'start_date' => '2027-01-01',
            'end_date' => '2027-12-31',
        ]);

    $response->assertStatus(422);
    expect($response->json('code'))->toBe('ACADEMIC_YEAR_OVERLAP');
});

it('activating a year closes the previously active year', function () {
    $owner = ownerWithActiveSubscription();
    $schoolId = $owner->test_school->id;

    $currentYear = AcademicYear::factory()->create([
        'school_id' => $schoolId,
        'status' => 'ACTIVE',
        'start_date' => '2025-10-01',
        'end_date' => '2026-07-31',
    ]);

    $newYear = AcademicYear::factory()->create([
        'school_id' => $schoolId,
        'status' => 'DRAFT',
        'start_date' => '2026-10-01',
        'end_date' => '2027-07-31',
    ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/years/{$newYear->id}/activate");

    $response->assertOk();
    $response->assertJsonPath('data.status', 'ACTIVE');

    expect($currentYear->fresh()->status->value)->toBe('CLOSED');
    expect($newYear->fresh()->status->value)->toBe('ACTIVE');
});

it('blocks modification of a closed academic year', function () {
    $owner = ownerWithActiveSubscription();
    $year = AcademicYear::factory()->create(['school_id' => $owner->test_school->id, 'status' => 'CLOSED']);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->putJson("/api/v1/academic/years/{$year->id}", ['name' => 'Nouveau nom']);

    $response->assertStatus(422);
    expect($response->json('code'))->toBe('ACADEMIC_YEAR_CLOSED');
});

it('archives and restores an academic year', function () {
    $owner = ownerWithActiveSubscription();
    $year = AcademicYear::factory()->create(['school_id' => $owner->test_school->id, 'status' => 'CLOSED']);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->deleteJson("/api/v1/academic/years/{$year->id}")
        ->assertOk();

    $this->assertSoftDeleted('academic_years', ['id' => $year->id]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/years/{$year->id}/restore")
        ->assertOk()
        ->assertJsonPath('data.status', 'CLOSED');
});

it('denies a role without the academic-year.manage permission', function () {
    $owner = ownerWithActiveSubscription();
    $teacher = staffMember($owner->test_school, RoleEnum::Teacher);

    $response = $this->withHeader('Authorization', 'Bearer ' . $teacher->test_token)
        ->postJson('/api/v1/academic/years', [
            'name' => '2026 - 2027',
            'start_date' => '2026-10-01',
            'end_date' => '2027-07-31',
        ]);

    $response->assertStatus(403);
});

it('allows a teacher to view academic years but not create one', function () {
    $owner = ownerWithActiveSubscription();
    $teacher = staffMember($owner->test_school, RoleEnum::Teacher);
    AcademicYear::factory()->create(['school_id' => $owner->test_school->id]);

    $this->withHeader('Authorization', 'Bearer ' . $teacher->test_token)
        ->getJson('/api/v1/academic/years')
        ->assertOk();
});

it('refuses a partial update that pushes start_date past the existing end_date', function () {
    $owner = ownerWithActiveSubscription();
    $year = AcademicYear::factory()->create([
        'school_id' => $owner->test_school->id,
        'status' => 'DRAFT',
        'start_date' => '2026-10-01',
        'end_date' => '2027-07-31',
    ]);

    // Only start_date is sent — end_date's "after:start_date" FormRequest rule
    // never runs for an absent field, so the Service must catch this itself.
    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->putJson("/api/v1/academic/years/{$year->id}", ['start_date' => '2028-01-01']);

    $response->assertStatus(422);
    expect($year->fresh()->start_date->toDateString())->toBe('2026-10-01');
});

it('isolates academic years between schools', function () {
    $ownerA = ownerWithActiveSubscription();
    $ownerB = ownerWithActiveSubscription();

    $yearB = AcademicYear::factory()->create(['school_id' => $ownerB->test_school->id]);

    $this->withHeader('Authorization', 'Bearer ' . $ownerA->test_token)
        ->getJson("/api/v1/academic/years/{$yearB->id}")
        ->assertStatus(403);
});
