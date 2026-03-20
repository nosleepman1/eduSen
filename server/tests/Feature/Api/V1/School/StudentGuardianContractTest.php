<?php

use App\Enums\RoleEnum;
use App\Models\Guardian;
use App\Models\School;
use App\Models\Student;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

function studentAndGuardian(School $school): array
{
    $student = Student::create([
        'school_id' => $school->id,
        'student_number' => 'STD-'.uniqid(),
        'firstname' => 'Fatou',
        'lastname' => 'Sow',
        'nationality' => 'Sénégalaise',
        'status' => 'ACTIVE',
        'enrolled_at' => now(),
    ]);

    $guardian = Guardian::create([
        'school_id' => $school->id,
        'firstname' => 'Ibrahima',
        'lastname' => 'Sow',
        'phone' => '77'.rand(1000000, 9999999),
    ]);

    return [$student, $guardian];
}

it('exposes the pivot data on show after linking a guardian', function () {
    $owner = ownerWithActiveSubscription();
    $admin = staffMember($owner->test_school, RoleEnum::Admin);
    [$student, $guardian] = studentAndGuardian($owner->test_school);

    test()->withToken($admin->test_token)
        ->postJson("/api/v1/students/{$student->id}/guardians", [
            'guardian_id' => $guardian->id,
            'relationship' => 'FATHER',
            'is_primary' => true,
        ])
        ->assertOk();

    $response = test()->withToken($admin->test_token)
        ->getJson("/api/v1/students/{$student->id}")
        ->assertOk();

    $response->assertJsonPath('data.guardians.0.id', $guardian->id)
        ->assertJsonPath('data.guardians.0.relationship', 'FATHER')
        ->assertJsonPath('data.guardians.0.is_primary_contact', true);
});

it('updates the pivot when the same guardian is linked again with a different relationship', function () {
    $owner = ownerWithActiveSubscription();
    $admin = staffMember($owner->test_school, RoleEnum::Admin);
    [$student, $guardian] = studentAndGuardian($owner->test_school);

    test()->withToken($admin->test_token)->postJson("/api/v1/students/{$student->id}/guardians", [
        'guardian_id' => $guardian->id,
        'relationship' => 'FATHER',
    ])->assertOk();

    test()->withToken($admin->test_token)->postJson("/api/v1/students/{$student->id}/guardians", [
        'guardian_id' => $guardian->id,
        'relationship' => 'TUTOR',
    ])->assertOk();

    $response = test()->withToken($admin->test_token)->getJson("/api/v1/students/{$student->id}")->assertOk();

    expect($response->json('data.guardians'))->toHaveCount(1);
    $response->assertJsonPath('data.guardians.0.relationship', 'TUTOR');
});

it('does not load guardians on the index listing', function () {
    $owner = ownerWithActiveSubscription();
    $admin = staffMember($owner->test_school, RoleEnum::Admin);
    [$student, $guardian] = studentAndGuardian($owner->test_school);

    test()->withToken($admin->test_token)->postJson("/api/v1/students/{$student->id}/guardians", [
        'guardian_id' => $guardian->id,
        'relationship' => 'MOTHER',
    ])->assertOk();

    // index() intentionally skips eager-loading guardians (would be N+1 across
    // a page); whenLoaded() means the key is simply absent, not an empty array.
    test()->withToken($admin->test_token)
        ->getJson('/api/v1/students')
        ->assertOk()
        ->assertJsonMissingPath('data.0.guardians');
});
