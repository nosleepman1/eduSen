<?php

use App\Enums\RoleEnum;
use App\Models\AcademicYear;
use App\Models\Level;

it('creates a level via the API', function () {
    $owner = ownerWithActiveSubscription();
    $year = AcademicYear::factory()->create(['school_id' => $owner->test_school->id]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/levels', [
            'academic_year_id' => $year->id,
            'name' => '6ème',
            'code' => 'L6',
        ]);

    $response->assertCreated();
    $this->assertDatabaseHas('levels', [
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $year->id,
        'name' => '6ème',
    ]);
});

it('assigns the next display_order automatically when omitted', function () {
    $owner = ownerWithActiveSubscription();
    $year = AcademicYear::factory()->create(['school_id' => $owner->test_school->id]);
    // Pinned name/code: the request below hardcodes "CM2", which is also in
    // the factory's random name pool — an unpinned name would occasionally
    // collide and fail on uniqueness instead of asserting display_order.
    Level::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $year->id,
        'name' => 'Existant',
        'code' => 'EXISTANT',
        'display_order' => 5,
    ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/levels', [
            'academic_year_id' => $year->id,
            'name' => 'CM2',
            'code' => 'CM2',
        ]);

    $response->assertCreated();
    $response->assertJsonPath('data.display_order', 6);
});

it('refuses two levels with the same name in the same academic year (RL-002)', function () {
    $owner = ownerWithActiveSubscription();
    $year = AcademicYear::factory()->create(['school_id' => $owner->test_school->id]);
    Level::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $year->id,
        'name' => 'Terminale',
    ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/levels', [
            'academic_year_id' => $year->id,
            'name' => 'Terminale',
            'code' => 'TLE-2',
        ]);

    $response->assertStatus(422);
});

it('refuses two levels with the same code in the same academic year (RL-003)', function () {
    $owner = ownerWithActiveSubscription();
    $year = AcademicYear::factory()->create(['school_id' => $owner->test_school->id]);
    Level::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $year->id,
        'code' => 'TLE',
    ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/levels', [
            'academic_year_id' => $year->id,
            'name' => 'Terminale bis',
            'code' => 'TLE',
        ]);

    $response->assertStatus(422);
});

it('allows the same level name across two different academic years (RL-002)', function () {
    $owner = ownerWithActiveSubscription();
    $yearA = AcademicYear::factory()->create([
        'school_id' => $owner->test_school->id,
        'start_date' => '2026-10-01',
        'end_date' => '2027-07-31',
    ]);
    $yearB = AcademicYear::factory()->create([
        'school_id' => $owner->test_school->id,
        'start_date' => '2027-10-01',
        'end_date' => '2028-07-31',
    ]);
    Level::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $yearA->id,
        'name' => '6ème',
        'code' => 'L6-A',
    ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/levels', [
            'academic_year_id' => $yearB->id,
            'name' => '6ème',
            'code' => 'L6-B',
        ]);

    $response->assertCreated();
});

it('archives and restores a level', function () {
    $owner = ownerWithActiveSubscription();
    $level = Level::factory()->create(['school_id' => $owner->test_school->id]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->deleteJson("/api/v1/academic/levels/{$level->id}")
        ->assertOk();

    $this->assertSoftDeleted('levels', ['id' => $level->id]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/levels/{$level->id}/restore")
        ->assertOk();

    $this->assertDatabaseHas('levels', ['id' => $level->id, 'deleted_at' => null]);
});

it('reorders levels within a year atomically', function () {
    $owner = ownerWithActiveSubscription();
    $year = AcademicYear::factory()->create(['school_id' => $owner->test_school->id]);
    $first = Level::factory()->create(['school_id' => $owner->test_school->id, 'academic_year_id' => $year->id, 'display_order' => 1]);
    $second = Level::factory()->create(['school_id' => $owner->test_school->id, 'academic_year_id' => $year->id, 'display_order' => 2]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson('/api/v1/academic/levels/reorder', [
            'academic_year_id' => $year->id,
            'ordered_ids' => [$second->id, $first->id],
        ]);

    $response->assertOk();
    expect($second->fresh()->display_order)->toBe(1);
    expect($first->fresh()->display_order)->toBe(2);
});

it('denies a role without the level.manage permission', function () {
    $owner = ownerWithActiveSubscription();
    $teacher = staffMember($owner->test_school, RoleEnum::Teacher);
    $year = AcademicYear::factory()->create(['school_id' => $owner->test_school->id]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $teacher->test_token)
        ->postJson('/api/v1/academic/levels', [
            'academic_year_id' => $year->id,
            'name' => '6ème',
            'code' => 'L6',
        ]);

    $response->assertStatus(403);
});

it('allows a teacher to view levels but not create one', function () {
    $owner = ownerWithActiveSubscription();
    $teacher = staffMember($owner->test_school, RoleEnum::Teacher);
    Level::factory()->create(['school_id' => $owner->test_school->id]);

    $this->withHeader('Authorization', 'Bearer ' . $teacher->test_token)
        ->getJson('/api/v1/academic/levels')
        ->assertOk();
});

it('isolates levels between schools', function () {
    $ownerA = ownerWithActiveSubscription();
    $ownerB = ownerWithActiveSubscription();

    $levelB = Level::factory()->create(['school_id' => $ownerB->test_school->id]);

    $this->withHeader('Authorization', 'Bearer ' . $ownerA->test_token)
        ->getJson("/api/v1/academic/levels/{$levelB->id}")
        ->assertStatus(403);
});
