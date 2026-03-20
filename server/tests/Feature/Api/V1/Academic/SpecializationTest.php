<?php

use App\Models\Specialization;

it('creates a specialization', function () {
    $owner = ownerWithActiveSubscription();

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/specializations', [
            'name' => 'Terminale Scientifique',
            'code' => 'S',
        ]);

    $response->assertCreated();
    $this->assertDatabaseHas('specializations', ['school_id' => $owner->test_school->id, 'code' => 'S']);
});

it('refuses a duplicate specialization code within the same school', function () {
    $owner = ownerWithActiveSubscription();
    Specialization::factory()->create(['school_id' => $owner->test_school->id, 'code' => 'S']);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/specializations', [
            'name' => 'Scientifique bis',
            'code' => 'S',
        ]);

    $response->assertStatus(422);
});

it('archives and restores a specialization', function () {
    $owner = ownerWithActiveSubscription();
    $specialization = Specialization::factory()->create(['school_id' => $owner->test_school->id]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->deleteJson("/api/v1/academic/specializations/{$specialization->id}")
        ->assertOk();

    $this->assertSoftDeleted('specializations', ['id' => $specialization->id]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->patchJson("/api/v1/academic/specializations/{$specialization->id}/restore")
        ->assertOk();

    $this->assertDatabaseHas('specializations', ['id' => $specialization->id, 'deleted_at' => null]);
});
