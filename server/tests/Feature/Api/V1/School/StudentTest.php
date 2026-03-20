<?php

use App\Enums\RoleEnum;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use App\Models\UserRole;

it('creates a student as owner', function () {
    $owner = ownerWithActiveSubscription();

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/students', [
            'firstname' => 'Moussa',
            'lastname' => 'Diallo',
            'gender' => 'MALE',
        ]);

    $response->assertCreated();
    $this->assertDatabaseHas('students', ['school_id' => $owner->test_school->id, 'firstname' => 'Moussa']);
});

it('allows a secretary to create a student', function () {
    $owner = ownerWithActiveSubscription();
    $secretary = staffMember($owner->test_school, RoleEnum::Secretary);

    $this->withHeader('Authorization', 'Bearer ' . $secretary->test_token)
        ->postJson('/api/v1/students', ['firstname' => 'Awa', 'lastname' => 'Ndiaye'])
        ->assertCreated();
});

it('denies a teacher from creating a student (RPR-004)', function () {
    $owner = ownerWithActiveSubscription();
    $teacher = staffMember($owner->test_school, RoleEnum::Teacher);

    $this->withHeader('Authorization', 'Bearer ' . $teacher->test_token)
        ->postJson('/api/v1/students', ['firstname' => 'Awa', 'lastname' => 'Ndiaye'])
        ->assertStatus(403);
});

it('refuses to create a student once the subscription max_students limit is reached', function () {
    $owner = ownerWithActiveSubscription();

    $plan = $owner->test_school->activeSubscription->plan;
    $plan->update(['max_students' => 1]);

    Student::factory()->create(['school_id' => $owner->test_school->id]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/students', ['firstname' => 'Fatou', 'lastname' => 'Sarr']);

    $response->assertStatus(422);
});

it('updates and archives a student', function () {
    $owner = ownerWithActiveSubscription();
    $student = Student::factory()->create(['school_id' => $owner->test_school->id]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->putJson("/api/v1/students/{$student->id}", ['firstname' => 'Nouveau Prénom'])
        ->assertOk()
        ->assertJsonPath('data.firstname', 'Nouveau Prénom');

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->deleteJson("/api/v1/students/{$student->id}")
        ->assertOk();

    $this->assertSoftDeleted('students', ['id' => $student->id]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/students/{$student->id}/restore")
        ->assertOk();
});

it('links a guardian to a student as the primary contact', function () {
    $owner = ownerWithActiveSubscription();
    $student = Student::factory()->create(['school_id' => $owner->test_school->id]);
    $guardian = Guardian::factory()->create(['school_id' => $owner->test_school->id]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson("/api/v1/students/{$student->id}/guardians", [
            'guardian_id' => $guardian->id,
            'relationship' => 'MOTHER',
            'is_primary' => true,
        ]);

    $response->assertOk();
    $this->assertDatabaseHas('guardian_student', [
        'student_id' => $student->id,
        'guardian_id' => $guardian->id,
        'is_primary_contact' => true,
    ]);
    // The response must reflect the link it just created, not just persist it silently.
    $response->assertJsonPath('data.guardians.0.id', $guardian->id);
});

it('returns a clean error instead of a 500 when a super admin has no school context', function () {
    $superAdmin = User::factory()->create();
    UserRole::create([
        'user_id' => $superAdmin->id,
        'school_id' => null,
        'role' => RoleEnum::SuperAdmin,
        'is_active' => true,
        'assigned_at' => now(),
    ]);
    $token = $superAdmin->createToken('api')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/v1/students', ['firstname' => 'Awa', 'lastname' => 'Ndiaye']);

    $response->assertStatus(403);
    expect($response->json('code'))->toBe('NO_SCHOOL_ASSOCIATED');
});

it('isolates students between schools', function () {
    $ownerA = ownerWithActiveSubscription();
    $ownerB = ownerWithActiveSubscription();

    $studentB = Student::factory()->create(['school_id' => $ownerB->test_school->id]);

    $this->withHeader('Authorization', 'Bearer ' . $ownerA->test_token)
        ->getJson("/api/v1/students/{$studentB->id}")
        ->assertStatus(403);
});
