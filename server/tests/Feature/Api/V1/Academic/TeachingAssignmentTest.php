<?php

use App\Enums\RoleEnum;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Curriculum;
use App\Models\Level;
use App\Models\Personnel;
use App\Models\Specialization;
use App\Models\Teacher;
use App\Models\TeachingAssignment;

function assignmentReadyClassroom(\App\Models\User $owner): Classroom
{
    $year = AcademicYear::factory()->create(['school_id' => $owner->test_school->id, 'status' => 'ACTIVE']);
    $level = Level::factory()->create(['school_id' => $owner->test_school->id, 'academic_year_id' => $year->id]);

    return Classroom::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $year->id,
        'level_id' => $level->id,
        'status' => 'ACTIVE',
    ]);
}

function activeClassroomWithCurriculum(\App\Models\User $owner, ?string $specializationId = null): array
{
    $classroom = assignmentReadyClassroom($owner);
    if ($specializationId !== null) {
        $classroom->update(['specialization_id' => $specializationId]);
    }

    $curriculum = Curriculum::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $classroom->academic_year_id,
        'level_id' => $classroom->level_id,
        'specialization_id' => $specializationId,
    ]);

    return [$classroom, $curriculum];
}

function teacherFor(\App\Models\User $owner): Teacher
{
    $personnel = Personnel::factory()->create(['school_id' => $owner->test_school->id]);

    return Teacher::factory()->create(['personnel_id' => $personnel->id]);
}

it('assigns a teacher to a compatible classroom/curriculum via the API', function () {
    $owner = ownerWithActiveSubscription();
    [$classroom, $curriculum] = activeClassroomWithCurriculum($owner);
    $teacher = teacherFor($owner);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/teaching-assignments', [
            'classroom_id' => $classroom->id,
            'curriculum_id' => $curriculum->id,
            'teacher_id' => $teacher->id,
        ]);

    $response->assertCreated();
    $this->assertDatabaseHas('teaching_assignments', [
        'classroom_id' => $classroom->id,
        'curriculum_id' => $curriculum->id,
        'teacher_id' => $teacher->id,
        'role' => 'PRINCIPAL',
        'status' => 'ACTIVE',
    ]);
});

it('refuses an assignment when the curriculum belongs to another level (RTA-005)', function () {
    $owner = ownerWithActiveSubscription();
    $classroom = assignmentReadyClassroom($owner);
    $otherLevel = Level::factory()->create(['school_id' => $owner->test_school->id, 'academic_year_id' => $classroom->academic_year_id]);
    $curriculum = Curriculum::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $classroom->academic_year_id,
        'level_id' => $otherLevel->id,
    ]);
    $teacher = teacherFor($owner);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/teaching-assignments', [
            'classroom_id' => $classroom->id,
            'curriculum_id' => $curriculum->id,
            'teacher_id' => $teacher->id,
        ]);

    $response->assertStatus(422);
});

it('refuses an assignment when the curriculum specialization does not match the classroom (RTA-005)', function () {
    $owner = ownerWithActiveSubscription();
    $specializationS = Specialization::factory()->create(['school_id' => $owner->test_school->id, 'code' => 'S']);
    $specializationL = Specialization::factory()->create(['school_id' => $owner->test_school->id, 'code' => 'L']);

    [$classroom, ] = activeClassroomWithCurriculum($owner, $specializationS->id);
    $curriculumL = Curriculum::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $classroom->academic_year_id,
        'level_id' => $classroom->level_id,
        'specialization_id' => $specializationL->id,
    ]);
    $teacher = teacherFor($owner);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/teaching-assignments', [
            'classroom_id' => $classroom->id,
            'curriculum_id' => $curriculumL->id,
            'teacher_id' => $teacher->id,
        ]);

    $response->assertStatus(422);
});

it('refuses a second active Principal for the same subject in the same classroom (RTA-009)', function () {
    $owner = ownerWithActiveSubscription();
    [$classroom, $curriculum] = activeClassroomWithCurriculum($owner);
    $firstTeacher = teacherFor($owner);
    TeachingAssignment::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $classroom->academic_year_id,
        'classroom_id' => $classroom->id,
        'curriculum_id' => $curriculum->id,
        'teacher_id' => $firstTeacher->id,
        'role' => 'PRINCIPAL',
    ]);
    $secondTeacher = teacherFor($owner);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/teaching-assignments', [
            'classroom_id' => $classroom->id,
            'curriculum_id' => $curriculum->id,
            'teacher_id' => $secondTeacher->id,
        ]);

    $response->assertStatus(422);
});

it('allows a co-teacher alongside an existing active Principal (RTA-009)', function () {
    $owner = ownerWithActiveSubscription();
    [$classroom, $curriculum] = activeClassroomWithCurriculum($owner);
    $principal = teacherFor($owner);
    TeachingAssignment::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $classroom->academic_year_id,
        'classroom_id' => $classroom->id,
        'curriculum_id' => $curriculum->id,
        'teacher_id' => $principal->id,
        'role' => 'PRINCIPAL',
    ]);
    $coTeacher = teacherFor($owner);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/teaching-assignments', [
            'classroom_id' => $classroom->id,
            'curriculum_id' => $curriculum->id,
            'teacher_id' => $coTeacher->id,
            'role' => 'CO_TEACHER',
        ]);

    $response->assertCreated();
});

it('refuses a new assignment once the subscription plan max_teachers is reached (RTA-011)', function () {
    $owner = ownerWithActiveSubscription();
    $owner->test_school->activeSubscription->plan->update(['max_teachers' => 1]);
    [$classroomA, $curriculumA] = activeClassroomWithCurriculum($owner);
    $firstTeacher = teacherFor($owner);
    TeachingAssignment::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $classroomA->academic_year_id,
        'classroom_id' => $classroomA->id,
        'curriculum_id' => $curriculumA->id,
        'teacher_id' => $firstTeacher->id,
    ]);

    [$classroomB, $curriculumB] = activeClassroomWithCurriculum($owner);
    $secondTeacher = teacherFor($owner);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/teaching-assignments', [
            'classroom_id' => $classroomB->id,
            'curriculum_id' => $curriculumB->id,
            'teacher_id' => $secondTeacher->id,
        ]);

    $response->assertStatus(422);
});

it('reassigns a teacher, closing the old assignment and opening a new one', function () {
    $owner = ownerWithActiveSubscription();
    [$classroom, $curriculum] = activeClassroomWithCurriculum($owner);
    $oldTeacher = teacherFor($owner);
    $assignment = TeachingAssignment::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $classroom->academic_year_id,
        'classroom_id' => $classroom->id,
        'curriculum_id' => $curriculum->id,
        'teacher_id' => $oldTeacher->id,
    ]);
    $newTeacher = teacherFor($owner);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/teaching-assignments/{$assignment->id}/reassign", [
            'teacher_id' => $newTeacher->id,
        ]);

    $response->assertOk();
    expect($response->json('data.teacher_id'))->toBe($newTeacher->id);
    $this->assertDatabaseHas('teaching_assignments', ['id' => $assignment->id, 'status' => 'CLOSED']);
    $this->assertDatabaseHas('teaching_assignments', [
        'classroom_id' => $classroom->id,
        'curriculum_id' => $curriculum->id,
        'teacher_id' => $newTeacher->id,
        'status' => 'ACTIVE',
    ]);
});

it('computes a teacher weekly workload from active assignments', function () {
    $owner = ownerWithActiveSubscription();
    $teacher = teacherFor($owner);

    [$classroomA, $curriculumA] = activeClassroomWithCurriculum($owner);
    $curriculumA->update(['weekly_hours' => 5]);
    TeachingAssignment::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $classroomA->academic_year_id,
        'classroom_id' => $classroomA->id,
        'curriculum_id' => $curriculumA->id,
        'teacher_id' => $teacher->id,
    ]);

    [$classroomB, $curriculumB] = activeClassroomWithCurriculum($owner);
    $curriculumB->update(['weekly_hours' => 3]);
    TeachingAssignment::factory()->create([
        'school_id' => $owner->test_school->id,
        'academic_year_id' => $classroomB->academic_year_id,
        'classroom_id' => $classroomB->id,
        'curriculum_id' => $curriculumB->id,
        'teacher_id' => $teacher->id,
    ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->getJson("/api/v1/academic/teachers/{$teacher->id}/workload");

    $response->assertOk();
    expect($response->json('data.weekly_hours'))->toBe(8);
});

it('closes and restores (via archive) a teaching assignment', function () {
    $owner = ownerWithActiveSubscription();
    $assignment = TeachingAssignment::factory()->create(['school_id' => $owner->test_school->id]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/teaching-assignments/{$assignment->id}/close")
        ->assertOk();

    $this->assertDatabaseHas('teaching_assignments', ['id' => $assignment->id, 'status' => 'CLOSED']);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->deleteJson("/api/v1/academic/teaching-assignments/{$assignment->id}")
        ->assertOk();

    $this->assertSoftDeleted('teaching_assignments', ['id' => $assignment->id]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/teaching-assignments/{$assignment->id}/restore")
        ->assertOk();
});

it('denies a role without the teaching-assignment.manage permission', function () {
    $owner = ownerWithActiveSubscription();
    $teacherRole = staffMember($owner->test_school, RoleEnum::Teacher);
    [$classroom, $curriculum] = activeClassroomWithCurriculum($owner);
    $teacher = teacherFor($owner);

    $response = $this->withHeader('Authorization', 'Bearer ' . $teacherRole->test_token)
        ->postJson('/api/v1/academic/teaching-assignments', [
            'classroom_id' => $classroom->id,
            'curriculum_id' => $curriculum->id,
            'teacher_id' => $teacher->id,
        ]);

    $response->assertStatus(403);
});

it('isolates teaching assignments between schools', function () {
    $ownerA = ownerWithActiveSubscription();
    $ownerB = ownerWithActiveSubscription();

    $assignmentB = TeachingAssignment::factory()->create(['school_id' => $ownerB->test_school->id]);

    $this->withHeader('Authorization', 'Bearer ' . $ownerA->test_token)
        ->getJson("/api/v1/academic/teaching-assignments/{$assignmentB->id}")
        ->assertStatus(403);
});
