<?php

use App\Enums\RoleEnum;
use App\Models\Subject;

it('creates a subject', function () {
    $owner = ownerWithActiveSubscription();

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/subjects', [
            'name' => 'Mathématiques',
            'code' => 'MATH',
        ]);

    $response->assertCreated();
    $this->assertDatabaseHas('subjects', ['school_id' => $owner->test_school->id, 'code' => 'MATH']);
});

it('refuses a duplicate subject code within the same school (RS-002)', function () {
    $owner = ownerWithActiveSubscription();
    Subject::factory()->create(['school_id' => $owner->test_school->id, 'code' => 'MATH']);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/subjects', [
            'name' => 'Mathématiques bis',
            'code' => 'MATH',
        ]);

    $response->assertStatus(422);
});

it('refuses a duplicate subject name within the same school (RS-003)', function () {
    $owner = ownerWithActiveSubscription();
    Subject::factory()->create(['school_id' => $owner->test_school->id, 'name' => 'Mathématiques']);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/subjects', [
            'name' => 'Mathématiques',
            'code' => 'MATH-2',
        ]);

    $response->assertStatus(422);
});

it('archives and restores a subject', function () {
    $owner = ownerWithActiveSubscription();
    $subject = Subject::factory()->create(['school_id' => $owner->test_school->id]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->deleteJson("/api/v1/academic/subjects/{$subject->id}")
        ->assertOk();

    $this->assertSoftDeleted('subjects', ['id' => $subject->id]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/subjects/{$subject->id}/restore")
        ->assertOk();

    $this->assertDatabaseHas('subjects', ['id' => $subject->id, 'deleted_at' => null]);
});

it('denies a role without the subject.manage permission', function () {
    $owner = ownerWithActiveSubscription();
    $teacher = staffMember($owner->test_school, RoleEnum::Teacher);

    $response = $this->withHeader('Authorization', 'Bearer ' . $teacher->test_token)
        ->postJson('/api/v1/academic/subjects', [
            'name' => 'Mathématiques',
            'code' => 'MATH',
        ]);

    $response->assertStatus(403);
});

it('isolates subjects between schools', function () {
    $ownerA = ownerWithActiveSubscription();
    $ownerB = ownerWithActiveSubscription();

    $subjectB = Subject::factory()->create(['school_id' => $ownerB->test_school->id]);

    $this->withHeader('Authorization', 'Bearer ' . $ownerA->test_token)
        ->getJson("/api/v1/academic/subjects/{$subjectB->id}")
        ->assertStatus(403);
});
