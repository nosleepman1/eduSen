<?php

use App\Enums\RoleEnum;
use App\Models\AttendanceRecord;
use App\Models\Classroom;
use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\LessonSession;
use App\Models\Personnel;
use App\Models\ScheduleEntry;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\Timetable;

function attendanceReadyFor(\App\Models\User $owner, string $sessionStatus = 'IN_PROGRESS'): array
{
    $year = \App\Models\AcademicYear::factory()->create(['school_id' => $owner->test_school->id, 'status' => 'ACTIVE']);
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
    $entry = ScheduleEntry::factory()->create([
        'school_id' => $owner->test_school->id,
        'timetable_id' => $timetable->id,
        'teaching_assignment_id' => $assignment->id,
    ]);
    $session = LessonSession::factory()->create([
        'school_id' => $owner->test_school->id,
        'schedule_entry_id' => $entry->id,
        'status' => $sessionStatus,
    ]);
    $enrollment = Enrollment::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $year->id,
        'classroom_id' => $classroom->id,
    ]);

    return [$session, $enrollment, $classroom];
}

it('creates an attendance record for an enrolled student during an in-progress session', function () {
    $owner = ownerWithActiveSubscription();
    [$session, $enrollment] = attendanceReadyFor($owner, 'IN_PROGRESS');

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/attendance-records', [
            'lesson_session_id' => $session->id,
            'enrollment_id' => $enrollment->id,
            'status' => 'PRESENT',
        ]);

    $response->assertCreated();
    $this->assertDatabaseHas('attendance_records', [
        'lesson_session_id' => $session->id,
        'enrollment_id' => $enrollment->id,
        'status' => 'PRESENT',
    ]);
});

it('refuses a record for a student not enrolled in the session classroom (RAR-010)', function () {
    $owner = ownerWithActiveSubscription();
    [$session] = attendanceReadyFor($owner, 'IN_PROGRESS');
    $otherEnrollment = Enrollment::factory()->create(['school_id' => $owner->test_school->id]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/attendance-records', [
            'lesson_session_id' => $session->id,
            'enrollment_id' => $otherEnrollment->id,
        ]);

    $response->assertStatus(422);
});

it('refuses a record when the session has not started yet (RAR-004)', function () {
    $owner = ownerWithActiveSubscription();
    [$session, $enrollment] = attendanceReadyFor($owner, 'SCHEDULED');

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/attendance-records', [
            'lesson_session_id' => $session->id,
            'enrollment_id' => $enrollment->id,
        ]);

    $response->assertStatus(422);
});

it('refuses a duplicate record for the same session and enrollment (RAR-003)', function () {
    $owner = ownerWithActiveSubscription();
    [$session, $enrollment] = attendanceReadyFor($owner, 'IN_PROGRESS');
    AttendanceRecord::factory()->create([
        'school_id' => $owner->test_school->id,
        'lesson_session_id' => $session->id,
        'enrollment_id' => $enrollment->id,
    ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/attendance-records', [
            'lesson_session_id' => $session->id,
            'enrollment_id' => $enrollment->id,
        ]);

    $response->assertStatus(422);
});

it('refuses a Late status without an arrival time (RAR-006)', function () {
    $owner = ownerWithActiveSubscription();
    [$session, $enrollment] = attendanceReadyFor($owner, 'IN_PROGRESS');

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/attendance-records', [
            'lesson_session_id' => $session->id,
            'enrollment_id' => $enrollment->id,
            'status' => 'LATE',
        ]);

    $response->assertStatus(422);
});

it('bulk-generates absent records for every active enrollment of the classroom', function () {
    $owner = ownerWithActiveSubscription();
    [$session, , $classroom] = attendanceReadyFor($owner, 'IN_PROGRESS');
    $extraEnrollment = Enrollment::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $session->scheduleEntry->timetable->academic_year_id,
        'classroom_id' => $classroom->id,
    ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson("/api/v1/academic/lesson-sessions/{$session->id}/attendance/bulk");

    $response->assertCreated();
    expect($response->json('data'))->toHaveCount(2);
    $this->assertDatabaseHas('attendance_records', ['lesson_session_id' => $session->id, 'enrollment_id' => $extraEnrollment->id, 'status' => 'ABSENT']);
});

it('refuses a justification for a Present student', function () {
    $owner = ownerWithActiveSubscription();
    [$session, $enrollment] = attendanceReadyFor($owner, 'IN_PROGRESS');
    $record = AttendanceRecord::factory()->create([
        'school_id' => $owner->test_school->id,
        'lesson_session_id' => $session->id,
        'enrollment_id' => $enrollment->id,
        'status' => 'PRESENT',
    ]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/attendance-records/{$record->id}/justify", ['justification' => 'Certificat médical'])
        ->assertStatus(422);
});

it('justifies an absence and approves it, updating the status to Excused', function () {
    $owner = ownerWithActiveSubscription();
    [$session, $enrollment] = attendanceReadyFor($owner, 'IN_PROGRESS');
    $record = AttendanceRecord::factory()->create([
        'school_id' => $owner->test_school->id,
        'lesson_session_id' => $session->id,
        'enrollment_id' => $enrollment->id,
        'status' => 'ABSENT',
    ]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/attendance-records/{$record->id}/justify", ['justification' => 'Certificat médical'])
        ->assertOk();

    $this->assertDatabaseHas('attendance_records', ['id' => $record->id, 'justification_status' => 'PENDING']);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/attendance-records/{$record->id}/approve-justification")
        ->assertOk();

    $this->assertDatabaseHas('attendance_records', ['id' => $record->id, 'justification_status' => 'APPROVED', 'status' => 'EXCUSED']);
});

it('rejects a justification, leaving the absence unjustified', function () {
    $owner = ownerWithActiveSubscription();
    [$session, $enrollment] = attendanceReadyFor($owner, 'IN_PROGRESS');
    $record = AttendanceRecord::factory()->create([
        'school_id' => $owner->test_school->id,
        'lesson_session_id' => $session->id,
        'enrollment_id' => $enrollment->id,
        'status' => 'ABSENT',
        'justification' => 'Motif douteux',
        'justification_status' => 'PENDING',
    ]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/attendance-records/{$record->id}/reject-justification")
        ->assertOk();

    $this->assertDatabaseHas('attendance_records', ['id' => $record->id, 'justification_status' => 'REJECTED', 'status' => 'ABSENT']);
});

it('refuses to modify a record whose session is archived (RAR-005)', function () {
    $owner = ownerWithActiveSubscription();
    [$session, $enrollment] = attendanceReadyFor($owner, 'ARCHIVED');
    $record = AttendanceRecord::factory()->create([
        'school_id' => $owner->test_school->id,
        'lesson_session_id' => $session->id,
        'enrollment_id' => $enrollment->id,
    ]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->putJson("/api/v1/academic/attendance-records/{$record->id}", ['status' => 'PRESENT'])
        ->assertStatus(422);
});

it('computes an attendance summary for an enrollment', function () {
    $owner = ownerWithActiveSubscription();
    [, $enrollment] = attendanceReadyFor($owner, 'IN_PROGRESS');
    AttendanceRecord::factory()->create(['school_id' => $owner->test_school->id, 'enrollment_id' => $enrollment->id, 'status' => 'PRESENT']);
    AttendanceRecord::factory()->create(['school_id' => $owner->test_school->id, 'enrollment_id' => $enrollment->id, 'status' => 'PRESENT']);
    AttendanceRecord::factory()->create(['school_id' => $owner->test_school->id, 'enrollment_id' => $enrollment->id, 'status' => 'ABSENT']);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->getJson("/api/v1/academic/enrollments/{$enrollment->id}/attendance-summary");

    $response->assertOk();
    expect($response->json('data.total'))->toBe(3);
    expect($response->json('data.present'))->toBe(2);
    expect($response->json('data.absent'))->toBe(1);
    expect($response->json('data.attendance_rate'))->toBe(66.7);
});

it('archives and restores an attendance record', function () {
    $owner = ownerWithActiveSubscription();
    $record = AttendanceRecord::factory()->create(['school_id' => $owner->test_school->id]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->deleteJson("/api/v1/academic/attendance-records/{$record->id}")
        ->assertOk();

    $this->assertSoftDeleted('attendance_records', ['id' => $record->id]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/attendance-records/{$record->id}/restore")
        ->assertOk();
});

it('denies a role without the attendance-record.manage permission', function () {
    $owner = ownerWithActiveSubscription();
    $secretary = staffMember($owner->test_school, RoleEnum::Secretary);
    [$session, $enrollment] = attendanceReadyFor($owner, 'IN_PROGRESS');

    $response = $this->withHeader('Authorization', 'Bearer ' . $secretary->test_token)
        ->postJson('/api/v1/academic/attendance-records', [
            'lesson_session_id' => $session->id,
            'enrollment_id' => $enrollment->id,
        ]);

    $response->assertStatus(403);
});

it('isolates attendance records between schools', function () {
    $ownerA = ownerWithActiveSubscription();
    $ownerB = ownerWithActiveSubscription();

    $recordB = AttendanceRecord::factory()->create(['school_id' => $ownerB->test_school->id]);

    $this->withHeader('Authorization', 'Bearer ' . $ownerA->test_token)
        ->getJson("/api/v1/academic/attendance-records/{$recordB->id}")
        ->assertStatus(403);
});
