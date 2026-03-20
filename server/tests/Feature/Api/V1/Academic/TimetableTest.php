<?php

use App\Enums\RoleEnum;
use App\Models\Classroom;
use App\Models\Timetable;

it('creates a timetable via the API', function () {
    $owner = ownerWithActiveSubscription();
    $classroom = Classroom::factory()->create(['school_id' => $owner->test_school->id]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/timetables', [
            'classroom_id' => $classroom->id,
            'name' => 'Emploi du temps général',
        ]);

    $response->assertCreated();
    $this->assertDatabaseHas('timetables', [
        'classroom_id' => $classroom->id,
        'name' => 'Emploi du temps général',
        'status' => 'DRAFT',
    ]);
});

it('activates a timetable and automatically deactivates the previous active one (RTT-004)', function () {
    $owner = ownerWithActiveSubscription();
    $classroom = Classroom::factory()->create(['school_id' => $owner->test_school->id]);
    $current = Timetable::factory()->create([
        'school_id' => $owner->test_school->id,
        'classroom_id' => $classroom->id,
        'status' => 'ACTIVE',
    ]);
    $candidate = Timetable::factory()->create([
        'school_id' => $owner->test_school->id,
        'classroom_id' => $classroom->id,
        'status' => 'DRAFT',
    ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/timetables/{$candidate->id}/activate");

    $response->assertOk();
    $this->assertDatabaseHas('timetables', ['id' => $candidate->id, 'status' => 'ACTIVE']);
    $this->assertDatabaseHas('timetables', ['id' => $current->id, 'status' => 'DRAFT']);
});

it('duplicates a timetable as a new draft container', function () {
    $owner = ownerWithActiveSubscription();
    $timetable = Timetable::factory()->create([
        'school_id' => $owner->test_school->id,
        'name' => 'Emploi du temps 2026-2027',
    ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson("/api/v1/academic/timetables/{$timetable->id}/duplicate");

    $response->assertCreated();
    expect($response->json('data.name'))->toBe('Emploi du temps 2026-2027 (copie)');
    expect($response->json('data.status'))->toBe('DRAFT');
    expect($response->json('data.id'))->not->toBe($timetable->id);
});

it('archives and restores a timetable back to draft', function () {
    $owner = ownerWithActiveSubscription();
    $timetable = Timetable::factory()->create(['school_id' => $owner->test_school->id, 'status' => 'ACTIVE']);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->deleteJson("/api/v1/academic/timetables/{$timetable->id}")
        ->assertOk();

    $this->assertSoftDeleted('timetables', ['id' => $timetable->id]);
    $this->assertDatabaseHas('timetables', ['id' => $timetable->id, 'status' => 'ARCHIVED']);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/timetables/{$timetable->id}/restore")
        ->assertOk();

    $this->assertDatabaseHas('timetables', ['id' => $timetable->id, 'status' => 'DRAFT']);
});

it('denies a role without the timetable.manage permission', function () {
    $owner = ownerWithActiveSubscription();
    $teacher = staffMember($owner->test_school, RoleEnum::Teacher);
    $classroom = Classroom::factory()->create(['school_id' => $owner->test_school->id]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $teacher->test_token)
        ->postJson('/api/v1/academic/timetables', [
            'classroom_id' => $classroom->id,
            'name' => 'Emploi du temps général',
        ]);

    $response->assertStatus(403);
});

it('isolates timetables between schools', function () {
    $ownerA = ownerWithActiveSubscription();
    $ownerB = ownerWithActiveSubscription();

    $timetableB = Timetable::factory()->create(['school_id' => $ownerB->test_school->id]);

    $this->withHeader('Authorization', 'Bearer ' . $ownerA->test_token)
        ->getJson("/api/v1/academic/timetables/{$timetableB->id}")
        ->assertStatus(403);
});
