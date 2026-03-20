<?php

use App\Enums\RoleEnum;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Level;
use App\Models\Student;

function activeClassroomFor(\App\Models\User $owner): Classroom
{
    $year = AcademicYear::factory()->create(['school_id' => $owner->test_school->id, 'status' => 'ACTIVE']);
    $level = Level::factory()->create(['school_id' => $owner->test_school->id, 'academic_year_id' => $year->id]);

    return Classroom::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $year->id,
        'level_id' => $level->id,
        'status' => 'ACTIVE',
        'capacity' => 2,
    ]);
}

it('enrolls a student in an active classroom via the API', function () {
    $owner = ownerWithActiveSubscription();
    $classroom = activeClassroomFor($owner);
    $student = Student::factory()->create(['school_id' => $owner->test_school->id]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/enrollments', [
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);

    $response->assertCreated();
    expect($response->json('data.registration_number'))->toStartWith($owner->test_school->code . '-');
    $this->assertDatabaseHas('enrollments', [
        'classroom_id' => $classroom->id,
        'student_id' => $student->id,
        'status' => 'ACTIVE',
    ]);
});

it('refuses an enrollment in a non-active classroom (RE-006)', function () {
    $owner = ownerWithActiveSubscription();
    $classroom = activeClassroomFor($owner);
    $classroom->update(['status' => 'DRAFT']);
    $student = Student::factory()->create(['school_id' => $owner->test_school->id]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/enrollments', [
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);

    $response->assertStatus(422);
});

it('refuses an enrollment when the academic year is not active (RE-007)', function () {
    $owner = ownerWithActiveSubscription();
    $classroom = activeClassroomFor($owner);
    $classroom->academicYear->update(['status' => 'CLOSED']);
    $student = Student::factory()->create(['school_id' => $owner->test_school->id]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/enrollments', [
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);

    $response->assertStatus(422);
});

it('refuses an enrollment once the classroom capacity is reached (RE-005)', function () {
    $owner = ownerWithActiveSubscription();
    $classroom = activeClassroomFor($owner); // capacity = 2
    Enrollment::factory()->count(2)->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $classroom->academic_year_id,
        'classroom_id' => $classroom->id,
    ]);
    $student = Student::factory()->create(['school_id' => $owner->test_school->id]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/enrollments', [
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);

    $response->assertStatus(422);
});

it('refuses a second active enrollment for the same student in the same year (RE-004)', function () {
    $owner = ownerWithActiveSubscription();
    $classroom = activeClassroomFor($owner);
    $student = Student::factory()->create(['school_id' => $owner->test_school->id]);
    Enrollment::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $classroom->academic_year_id,
        'classroom_id' => $classroom->id,
        'student_id' => $student->id,
    ]);

    $otherClassroom = activeClassroomFor($owner);
    // Force the second classroom into the same academic year as the first.
    $otherClassroom->update(['academic_year_id' => $classroom->academic_year_id]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/enrollments', [
            'classroom_id' => $otherClassroom->id,
            'student_id' => $student->id,
        ]);

    $response->assertStatus(422);
});

it('refuses a new enrollment once the subscription plan max_students is reached (RE-011)', function () {
    $owner = ownerWithActiveSubscription();
    $owner->test_school->activeSubscription->plan->update(['max_students' => 1]);
    $classroom = activeClassroomFor($owner);
    Enrollment::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $classroom->academic_year_id,
        'classroom_id' => $classroom->id,
    ]);
    $student = Student::factory()->create(['school_id' => $owner->test_school->id]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/enrollments', [
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);

    $response->assertStatus(422);
});

it('transfers a student to another active classroom of the same year (RE-009)', function () {
    $owner = ownerWithActiveSubscription();
    $origin = activeClassroomFor($owner);
    $destination = activeClassroomFor($owner);
    $destination->update(['academic_year_id' => $origin->academic_year_id]);

    $enrollment = Enrollment::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $origin->academic_year_id,
        'classroom_id' => $origin->id,
    ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/enrollments/{$enrollment->id}/transfer", [
            'classroom_id' => $destination->id,
        ]);

    $response->assertOk();
    expect($response->json('data.classroom_id'))->toBe($destination->id);
    expect($response->json('data.id'))->not->toBe($enrollment->id);

    $this->assertDatabaseHas('enrollments', [
        'id' => $enrollment->id,
        'status' => 'TRANSFERRED',
    ]);
    $this->assertDatabaseHas('enrollments', [
        'classroom_id' => $destination->id,
        'student_id' => $enrollment->student_id,
        'status' => 'ACTIVE',
    ]);
});

it('refuses transferring a student to their current classroom', function () {
    $owner = ownerWithActiveSubscription();
    $classroom = activeClassroomFor($owner);
    $enrollment = Enrollment::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $classroom->academic_year_id,
        'classroom_id' => $classroom->id,
    ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/enrollments/{$enrollment->id}/transfer", [
            'classroom_id' => $classroom->id,
        ]);

    $response->assertStatus(422);
});

it('withdraws a student and closes the enrollment', function () {
    $owner = ownerWithActiveSubscription();
    $classroom = activeClassroomFor($owner);
    $enrollment = Enrollment::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $classroom->academic_year_id,
        'classroom_id' => $classroom->id,
    ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/enrollments/{$enrollment->id}/withdraw", [
            'reason' => "Départ vers un autre établissement",
        ]);

    $response->assertOk();
    $this->assertDatabaseHas('enrollments', [
        'id' => $enrollment->id,
        'status' => 'WITHDRAWN',
    ]);
});

it('archives and restores an enrollment', function () {
    $owner = ownerWithActiveSubscription();
    $enrollment = Enrollment::factory()->create(['school_id' => $owner->test_school->id]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->deleteJson("/api/v1/academic/enrollments/{$enrollment->id}")
        ->assertOk();

    $this->assertSoftDeleted('enrollments', ['id' => $enrollment->id]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/enrollments/{$enrollment->id}/restore")
        ->assertOk();
});

it('denies a role without the enrollment.manage permission', function () {
    $owner = ownerWithActiveSubscription();
    $teacher = staffMember($owner->test_school, RoleEnum::Teacher);
    $classroom = activeClassroomFor($owner);
    $student = Student::factory()->create(['school_id' => $owner->test_school->id]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $teacher->test_token)
        ->postJson('/api/v1/academic/enrollments', [
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);

    $response->assertStatus(403);
});

it('isolates enrollments between schools', function () {
    $ownerA = ownerWithActiveSubscription();
    $ownerB = ownerWithActiveSubscription();

    $enrollmentB = Enrollment::factory()->create(['school_id' => $ownerB->test_school->id]);

    $this->withHeader('Authorization', 'Bearer ' . $ownerA->test_token)
        ->getJson("/api/v1/academic/enrollments/{$enrollmentB->id}")
        ->assertStatus(403);
});
