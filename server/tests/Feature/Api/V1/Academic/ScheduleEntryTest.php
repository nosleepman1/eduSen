<?php

use App\Enums\RoleEnum;
use App\Models\Classroom;
use App\Models\Curriculum;
use App\Models\Personnel;
use App\Models\ScheduleEntry;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\Timetable;

function scheduleSetupFor(\App\Models\User $owner, int $weeklyHours = 2): array
{
    $classroom = Classroom::factory()->create(['school_id' => $owner->test_school->id, 'status' => 'ACTIVE']);
    $timetable = Timetable::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $classroom->academic_year_id,
        'classroom_id' => $classroom->id,
    ]);
    $curriculum = Curriculum::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $classroom->academic_year_id,
        'level_id' => $classroom->level_id,
        'specialization_id' => $classroom->specialization_id,
        'weekly_hours' => $weeklyHours,
    ]);
    $personnel = Personnel::factory()->create(['school_id' => $owner->test_school->id]);
    $teacher = Teacher::factory()->create(['personnel_id' => $personnel->id]);
    $assignment = TeachingAssignment::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $classroom->academic_year_id,
        'classroom_id' => $classroom->id,
        'curriculum_id' => $curriculum->id,
        'teacher_id' => $teacher->id,
    ]);

    return [$classroom, $timetable, $assignment, $teacher];
}

it('creates a schedule entry via the API', function () {
    $owner = ownerWithActiveSubscription();
    [, $timetable, $assignment] = scheduleSetupFor($owner, 2);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/schedule-entries', [
            'timetable_id' => $timetable->id,
            'teaching_assignment_id' => $assignment->id,
            'day_of_week' => 'MONDAY',
            'start_time' => '08:00',
            'end_time' => '10:00',
        ]);

    $response->assertCreated();
    expect($response->json('data.volume_warning'))->toBeNull();
    $this->assertDatabaseHas('schedule_entries', [
        'timetable_id' => $timetable->id,
        'teaching_assignment_id' => $assignment->id,
        'day_of_week' => 'MONDAY',
    ]);
});

it('refuses an end time before the start time (RSE-003)', function () {
    $owner = ownerWithActiveSubscription();
    [, $timetable, $assignment] = scheduleSetupFor($owner);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/schedule-entries', [
            'timetable_id' => $timetable->id,
            'teaching_assignment_id' => $assignment->id,
            'day_of_week' => 'MONDAY',
            'start_time' => '10:00',
            'end_time' => '08:00',
        ]);

    $response->assertStatus(422);
});

it('refuses two overlapping entries for the same classroom (RSE-004)', function () {
    $owner = ownerWithActiveSubscription();
    [$classroom, $timetable, $assignment] = scheduleSetupFor($owner, 4);
    ScheduleEntry::factory()->create([
        'school_id' => $owner->test_school->id,
        'timetable_id' => $timetable->id,
        'teaching_assignment_id' => $assignment->id,
        'day_of_week' => 'MONDAY',
        'start_time' => '08:00',
        'end_time' => '10:00',
    ]);

    // A different subject/teacher, same classroom, overlapping slot.
    $curriculum2 = Curriculum::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $classroom->academic_year_id,
        'level_id' => $classroom->level_id,
        'specialization_id' => $classroom->specialization_id,
    ]);
    $personnel2 = Personnel::factory()->create(['school_id' => $owner->test_school->id]);
    $teacher2 = Teacher::factory()->create(['personnel_id' => $personnel2->id]);
    $assignment2 = TeachingAssignment::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $classroom->academic_year_id,
        'classroom_id' => $classroom->id,
        'curriculum_id' => $curriculum2->id,
        'teacher_id' => $teacher2->id,
    ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/schedule-entries', [
            'timetable_id' => $timetable->id,
            'teaching_assignment_id' => $assignment2->id,
            'day_of_week' => 'MONDAY',
            'start_time' => '09:00',
            'end_time' => '11:00',
        ]);

    $response->assertStatus(422);
});

it('refuses two overlapping entries for the same teacher across different classrooms (RSE-005)', function () {
    $owner = ownerWithActiveSubscription();
    [, $timetable, $assignment, $teacher] = scheduleSetupFor($owner, 4);
    ScheduleEntry::factory()->create([
        'school_id' => $owner->test_school->id,
        'timetable_id' => $timetable->id,
        'teaching_assignment_id' => $assignment->id,
        'day_of_week' => 'MONDAY',
        'start_time' => '08:00',
        'end_time' => '10:00',
    ]);

    // Same teacher, different classroom/timetable/curriculum, overlapping slot.
    $classroom2 = Classroom::factory()->create(['school_id' => $owner->test_school->id, 'status' => 'ACTIVE']);
    $timetable2 = Timetable::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $classroom2->academic_year_id,
        'classroom_id' => $classroom2->id,
    ]);
    $curriculum2 = Curriculum::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $classroom2->academic_year_id,
        'level_id' => $classroom2->level_id,
        'specialization_id' => $classroom2->specialization_id,
    ]);
    $assignment2 = TeachingAssignment::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $classroom2->academic_year_id,
        'classroom_id' => $classroom2->id,
        'curriculum_id' => $curriculum2->id,
        'teacher_id' => $teacher->id,
    ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/schedule-entries', [
            'timetable_id' => $timetable2->id,
            'teaching_assignment_id' => $assignment2->id,
            'day_of_week' => 'MONDAY',
            'start_time' => '09:00',
            'end_time' => '11:00',
        ]);

    $response->assertStatus(422);
});

it('refuses two overlapping entries booking the same room (RSE-006)', function () {
    $owner = ownerWithActiveSubscription();
    [$classroomA, $timetableA, $assignmentA] = scheduleSetupFor($owner, 4);
    ScheduleEntry::factory()->create([
        'school_id' => $owner->test_school->id,
        'timetable_id' => $timetableA->id,
        'teaching_assignment_id' => $assignmentA->id,
        'day_of_week' => 'TUESDAY',
        'start_time' => '08:00',
        'end_time' => '10:00',
        'room' => 'Salle 12',
    ]);

    [, $timetableB, $assignmentB] = scheduleSetupFor($owner, 4);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/schedule-entries', [
            'timetable_id' => $timetableB->id,
            'teaching_assignment_id' => $assignmentB->id,
            'day_of_week' => 'TUESDAY',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'room' => 'Salle 12',
        ]);

    $response->assertStatus(422);
});

it('allows back-to-back entries that do not overlap', function () {
    $owner = ownerWithActiveSubscription();
    [, $timetable, $assignment] = scheduleSetupFor($owner, 4);
    ScheduleEntry::factory()->create([
        'school_id' => $owner->test_school->id,
        'timetable_id' => $timetable->id,
        'teaching_assignment_id' => $assignment->id,
        'day_of_week' => 'MONDAY',
        'start_time' => '08:00',
        'end_time' => '10:00',
    ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/schedule-entries', [
            'timetable_id' => $timetable->id,
            'teaching_assignment_id' => $assignment->id,
            'day_of_week' => 'MONDAY',
            'start_time' => '10:00',
            'end_time' => '12:00',
        ]);

    $response->assertCreated();
});

it('returns a non-blocking volume warning when scheduled hours do not match the curriculum', function () {
    $owner = ownerWithActiveSubscription();
    [, $timetable, $assignment] = scheduleSetupFor($owner, 6);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/schedule-entries', [
            'timetable_id' => $timetable->id,
            'teaching_assignment_id' => $assignment->id,
            'day_of_week' => 'MONDAY',
            'start_time' => '08:00',
            'end_time' => '10:00',
        ]);

    $response->assertCreated();
    expect($response->json('data.volume_warning'))->not->toBeNull();
});

it('archives and restores a schedule entry', function () {
    $owner = ownerWithActiveSubscription();
    $entry = ScheduleEntry::factory()->create(['school_id' => $owner->test_school->id]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->deleteJson("/api/v1/academic/schedule-entries/{$entry->id}")
        ->assertOk();

    $this->assertSoftDeleted('schedule_entries', ['id' => $entry->id]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/schedule-entries/{$entry->id}/restore")
        ->assertOk();
});

it('denies a role without the schedule-entry.manage permission', function () {
    $owner = ownerWithActiveSubscription();
    $teacherRole = staffMember($owner->test_school, RoleEnum::Teacher);
    [, $timetable, $assignment] = scheduleSetupFor($owner);

    $response = $this->withHeader('Authorization', 'Bearer ' . $teacherRole->test_token)
        ->postJson('/api/v1/academic/schedule-entries', [
            'timetable_id' => $timetable->id,
            'teaching_assignment_id' => $assignment->id,
            'day_of_week' => 'MONDAY',
            'start_time' => '08:00',
            'end_time' => '10:00',
        ]);

    $response->assertStatus(403);
});

it('isolates schedule entries between schools', function () {
    $ownerA = ownerWithActiveSubscription();
    $ownerB = ownerWithActiveSubscription();

    $entryB = ScheduleEntry::factory()->create(['school_id' => $ownerB->test_school->id]);

    $this->withHeader('Authorization', 'Bearer ' . $ownerA->test_token)
        ->getJson("/api/v1/academic/schedule-entries/{$entryB->id}")
        ->assertStatus(403);
});
