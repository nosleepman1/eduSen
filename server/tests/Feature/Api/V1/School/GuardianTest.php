<?php

use App\Models\Guardian;

it('creates a guardian', function () {
    $owner = ownerWithActiveSubscription();

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/guardians', [
            'firstname' => 'Ibrahima',
            'lastname' => 'Fall',
            'phone' => '770000001',
        ]);

    $response->assertCreated();
    $this->assertDatabaseHas('guardians', ['school_id' => $owner->test_school->id, 'phone' => '770000001']);
});

it('refuses a duplicate phone number within the same school', function () {
    $owner = ownerWithActiveSubscription();
    Guardian::factory()->create(['school_id' => $owner->test_school->id, 'phone' => '770000002']);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/guardians', [
            'firstname' => 'Ibrahima',
            'lastname' => 'Fall',
            'phone' => '770000002',
        ]);

    $response->assertStatus(422);
});

it('updates and archives a guardian', function () {
    $owner = ownerWithActiveSubscription();
    $guardian = Guardian::factory()->create(['school_id' => $owner->test_school->id]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->putJson("/api/v1/guardians/{$guardian->id}", ['profession' => 'Commerçant'])
        ->assertOk()
        ->assertJsonPath('data.profession', 'Commerçant');

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->deleteJson("/api/v1/guardians/{$guardian->id}")
        ->assertOk();

    $this->assertSoftDeleted('guardians', ['id' => $guardian->id]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/guardians/{$guardian->id}/restore")
        ->assertOk();
});

it('isolates guardians between schools', function () {
    $ownerA = ownerWithActiveSubscription();
    $ownerB = ownerWithActiveSubscription();

    $guardianB = Guardian::factory()->create(['school_id' => $ownerB->test_school->id]);

    $this->withHeader('Authorization', 'Bearer ' . $ownerA->test_token)
        ->getJson("/api/v1/guardians/{$guardianB->id}")
        ->assertStatus(403);
});
