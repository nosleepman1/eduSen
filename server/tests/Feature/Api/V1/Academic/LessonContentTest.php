<?php

use App\Enums\RoleEnum;
use App\Models\LessonContent;
use App\Models\LessonSession;

it('creates a lesson content entry for an in-progress session', function () {
    $owner = ownerWithActiveSubscription();
    $session = LessonSession::factory()->create(['school_id' => $owner->test_school->id, 'status' => 'IN_PROGRESS']);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/lesson-contents', [
            'lesson_session_id' => $session->id,
            'title' => 'Chapitre 1 — Fonctions linéaires',
            'progress_number' => 1,
        ]);

    $response->assertCreated();
    $this->assertDatabaseHas('lesson_contents', [
        'lesson_session_id' => $session->id,
        'title' => 'Chapitre 1 — Fonctions linéaires',
    ]);
});

it('refuses creating content for a session that has not started yet (RLC-002)', function () {
    $owner = ownerWithActiveSubscription();
    $session = LessonSession::factory()->create(['school_id' => $owner->test_school->id, 'status' => 'SCHEDULED']);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/lesson-contents', [
            'lesson_session_id' => $session->id,
            'title' => 'Chapitre 1',
        ]);

    $response->assertStatus(422);
});

it('refuses modifying content once the session is archived (RLC-003)', function () {
    $owner = ownerWithActiveSubscription();
    $session = LessonSession::factory()->create(['school_id' => $owner->test_school->id, 'status' => 'ARCHIVED']);
    $content = LessonContent::factory()->create(['school_id' => $owner->test_school->id, 'lesson_session_id' => $session->id]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->putJson("/api/v1/academic/lesson-contents/{$content->id}", ['title' => 'Nouveau titre']);

    $response->assertStatus(422);
});

it('lists the contents of a lesson session', function () {
    $owner = ownerWithActiveSubscription();
    $session = LessonSession::factory()->create(['school_id' => $owner->test_school->id, 'status' => 'IN_PROGRESS']);
    LessonContent::factory()->create(['school_id' => $owner->test_school->id, 'lesson_session_id' => $session->id]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->getJson("/api/v1/academic/lesson-sessions/{$session->id}/contents");

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
});

it('archives and restores a lesson content entry', function () {
    $owner = ownerWithActiveSubscription();
    $content = LessonContent::factory()->create(['school_id' => $owner->test_school->id]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->deleteJson("/api/v1/academic/lesson-contents/{$content->id}")
        ->assertOk();

    $this->assertSoftDeleted('lesson_contents', ['id' => $content->id]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/lesson-contents/{$content->id}/restore")
        ->assertOk();
});

it('denies a role without the lesson-content.manage permission', function () {
    $owner = ownerWithActiveSubscription();
    $secretary = staffMember($owner->test_school, RoleEnum::Secretary);
    $session = LessonSession::factory()->create(['school_id' => $owner->test_school->id, 'status' => 'IN_PROGRESS']);

    $response = $this->withHeader('Authorization', 'Bearer ' . $secretary->test_token)
        ->postJson('/api/v1/academic/lesson-contents', [
            'lesson_session_id' => $session->id,
            'title' => 'Chapitre 1',
        ]);

    $response->assertStatus(403);
});

it('isolates lesson contents between schools', function () {
    $ownerA = ownerWithActiveSubscription();
    $ownerB = ownerWithActiveSubscription();

    $contentB = LessonContent::factory()->create(['school_id' => $ownerB->test_school->id]);

    $this->withHeader('Authorization', 'Bearer ' . $ownerA->test_token)
        ->getJson("/api/v1/academic/lesson-contents/{$contentB->id}")
        ->assertStatus(403);
});
