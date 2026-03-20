<?php

use App\Enums\RoleEnum;
use App\Models\Classroom;
use App\Models\Curriculum;
use App\Models\HolidayEvent;
use App\Models\LessonSession;
use App\Models\Personnel;
use App\Models\SchoolCalendar;
use App\Models\ScheduleEntry;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\Timetable;

function scheduleEntryReadyFor(\App\Models\User $owner): ScheduleEntry
{
    $year = \App\Models\AcademicYear::factory()->create([
        'school_id' => $owner->test_school->id,
        'status' => 'ACTIVE',
        'start_date' => '2026-10-01',
        'end_date' => '2027-07-31',
    ]);
    $level = \App\Models\Level::factory()->create(['school_id' => $owner->test_school->id, 'academic_year_id' => $year->id]);
    $classroom = Classroom::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $year->id,
        'level_id' => $level->id,
        'status' => 'ACTIVE',
    ]);
    $timetable = Timetable::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $year->id,
        'classroom_id' => $classroom->id,
    ]);
    $curriculum = Curriculum::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $year->id,
        'level_id' => $level->id,
        'specialization_id' => $classroom->specialization_id,
        'weekly_hours' => 2,
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

    return ScheduleEntry::factory()->create([
        'school_id' => $owner->test_school->id,
        'timetable_id' => $timetable->id,
        'teaching_assignment_id' => $assignment->id,
        'day_of_week' => 'MONDAY',
        'start_time' => '08:00',
        'end_time' => '10:00',
    ]);
}

it('generates weekly sessions for a schedule entry across the academic year', function () {
    $owner = ownerWithActiveSubscription();
    $entry = scheduleEntryReadyFor($owner);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson("/api/v1/academic/schedule-entries/{$entry->id}/generate-sessions");

    $response->assertCreated();
    $createdCount = count($response->json('data.created'));
    // AcademicYearFactory spans Oct 1 -> Jul 31 (~43 weeks) — expect roughly one session per week.
    expect($createdCount)->toBeGreaterThan(35)->toBeLessThan(46);
    $this->assertDatabaseHas('lesson_sessions', ['schedule_entry_id' => $entry->id, 'status' => 'SCHEDULED']);
});

it('skips generating sessions that fall within a school calendar holiday range', function () {
    $owner = ownerWithActiveSubscription();
    $entry = scheduleEntryReadyFor($owner);
    $academicYearId = $entry->timetable->academic_year_id;

    $calendar = SchoolCalendar::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $academicYearId,
    ]);
    HolidayEvent::factory()->create([
        'school_id' => $owner->test_school->id,
        'school_calendar_id' => $calendar->id,
        'start_date' => '2026-12-21',
        'end_date' => '2027-01-04',
    ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson("/api/v1/academic/schedule-entries/{$entry->id}/generate-sessions");

    $response->assertCreated();
    expect($response->json('data.skipped_holiday'))->toBeGreaterThan(0);
    $this->assertDatabaseMissing('lesson_sessions', ['schedule_entry_id' => $entry->id, 'session_date' => '2026-12-21']);
});

it('regenerates only future scheduled sessions, leaving completed ones untouched', function () {
    $owner = ownerWithActiveSubscription();
    $entry = scheduleEntryReadyFor($owner);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson("/api/v1/academic/schedule-entries/{$entry->id}/generate-sessions")
        ->assertCreated();

    $completed = LessonSession::where('schedule_entry_id', $entry->id)->orderBy('session_date')->first();
    $completed->update(['status' => 'COMPLETED']);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson("/api/v1/academic/schedule-entries/{$entry->id}/regenerate");

    $response->assertOk();
    $this->assertDatabaseHas('lesson_sessions', ['id' => $completed->id, 'status' => 'COMPLETED']);
});

it('starts and finishes a session', function () {
    $owner = ownerWithActiveSubscription();
    $session = LessonSession::factory()->create(['school_id' => $owner->test_school->id, 'status' => 'SCHEDULED']);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson("/api/v1/academic/lesson-sessions/{$session->id}/start")
        ->assertOk();

    $this->assertDatabaseHas('lesson_sessions', ['id' => $session->id, 'status' => 'IN_PROGRESS']);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson("/api/v1/academic/lesson-sessions/{$session->id}/finish")
        ->assertOk();

    $this->assertDatabaseHas('lesson_sessions', ['id' => $session->id, 'status' => 'COMPLETED']);
});

it('refuses to finish a session that was never started', function () {
    $owner = ownerWithActiveSubscription();
    $session = LessonSession::factory()->create(['school_id' => $owner->test_school->id, 'status' => 'SCHEDULED']);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson("/api/v1/academic/lesson-sessions/{$session->id}/finish")
        ->assertStatus(422);
});

it('requires a reason to cancel a session', function () {
    $owner = ownerWithActiveSubscription();
    $session = LessonSession::factory()->create(['school_id' => $owner->test_school->id, 'status' => 'SCHEDULED']);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson("/api/v1/academic/lesson-sessions/{$session->id}/cancel", [])
        ->assertStatus(422);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson("/api/v1/academic/lesson-sessions/{$session->id}/cancel", ['reason' => 'Enseignant absent'])
        ->assertOk();

    $this->assertDatabaseHas('lesson_sessions', ['id' => $session->id, 'status' => 'CANCELLED', 'cancellation_reason' => 'Enseignant absent']);
});

it('resolves a pending-validation session administratively', function () {
    $owner = ownerWithActiveSubscription();
    $session = LessonSession::factory()->create(['school_id' => $owner->test_school->id, 'status' => 'PENDING_VALIDATION']);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson("/api/v1/academic/lesson-sessions/{$session->id}/validate", ['decision' => 'COMPLETED']);

    $response->assertOk();
    $this->assertDatabaseHas('lesson_sessions', ['id' => $session->id, 'status' => 'COMPLETED']);
});

it('requires a reason when validating a session as cancelled', function () {
    $owner = ownerWithActiveSubscription();
    $session = LessonSession::factory()->create(['school_id' => $owner->test_school->id, 'status' => 'PENDING_VALIDATION']);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson("/api/v1/academic/lesson-sessions/{$session->id}/validate", ['decision' => 'CANCELLED'])
        ->assertStatus(422);
});

it('refuses any action on an archived session (RLS-006)', function () {
    $owner = ownerWithActiveSubscription();
    $session = LessonSession::factory()->create(['school_id' => $owner->test_school->id, 'status' => 'ARCHIVED']);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson("/api/v1/academic/lesson-sessions/{$session->id}/start")
        ->assertStatus(422);
});

it('restores a soft-deleted session', function () {
    $owner = ownerWithActiveSubscription();
    $session = LessonSession::factory()->create(['school_id' => $owner->test_school->id]);
    $session->delete();

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/lesson-sessions/{$session->id}/restore")
        ->assertOk();

    $this->assertDatabaseHas('lesson_sessions', ['id' => $session->id, 'deleted_at' => null]);
});

it('lists only today\'s sessions on the today endpoint', function () {
    $owner = ownerWithActiveSubscription();
    $todaySession = LessonSession::factory()->create(['school_id' => $owner->test_school->id, 'session_date' => now()->toDateString()]);
    LessonSession::factory()->create(['school_id' => $owner->test_school->id, 'session_date' => now()->addMonth()->toDateString()]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->getJson('/api/v1/academic/lesson-sessions/today');

    $response->assertOk();
    $ids = collect($response->json('data'))->pluck('id');
    expect($ids)->toContain($todaySession->id);
    expect($ids)->toHaveCount(1);
});

it('denies a role without the lesson-session.manage permission', function () {
    $owner = ownerWithActiveSubscription();
    $secretary = staffMember($owner->test_school, RoleEnum::Secretary);
    $session = LessonSession::factory()->create(['school_id' => $owner->test_school->id, 'status' => 'SCHEDULED']);

    $this->withHeader('Authorization', 'Bearer ' . $secretary->test_token)
        ->postJson("/api/v1/academic/lesson-sessions/{$session->id}/start")
        ->assertStatus(403);
});

it('isolates lesson sessions between schools', function () {
    $ownerA = ownerWithActiveSubscription();
    $ownerB = ownerWithActiveSubscription();

    $sessionB = LessonSession::factory()->create(['school_id' => $ownerB->test_school->id]);

    $this->withHeader('Authorization', 'Bearer ' . $ownerA->test_token)
        ->getJson("/api/v1/academic/lesson-sessions/{$sessionB->id}")
        ->assertStatus(403);
});
