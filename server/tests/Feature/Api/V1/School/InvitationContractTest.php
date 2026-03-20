<?php

use App\Enums\RoleEnum;
use App\Models\Invitation;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

function inviteOwner(): array
{
    $owner = ownerWithActiveSubscription();

    return [$owner, $owner->test_school, staffMember($owner->test_school, RoleEnum::Admin)];
}

it('never exposes the invitation token', function () {
    [, $school, $admin] = inviteOwner();

    $store = test()->withToken($admin->test_token)
        ->postJson('/api/v1/invitations', [
            'email' => 'new.teacher@example.test',
            'firstname' => 'Awa',
            'lastname' => 'Ndiaye',
            'role' => 'TEACHER',
        ])
        ->assertCreated();

    $store->assertJsonMissingPath('data.token');

    $index = test()->withToken($admin->test_token)->getJson('/api/v1/invitations')->assertOk();
    $index->assertJsonMissingPath('data.0.token');
});

it('exposes pagination metadata on the invitations list, consistent with other list endpoints', function () {
    [, $school, $admin] = inviteOwner();

    Invitation::create([
        'school_id' => $school->id,
        'email' => 'a@example.test',
        'firstname' => 'A',
        'lastname' => 'B',
        'role' => 'TEACHER',
        'token' => hash('sha256', 'x'),
        'status' => 'PENDING',
        'expires_at' => now()->addDays(7),
        'created_by' => $admin->id,
    ]);

    $response = test()->withToken($admin->test_token)->getJson('/api/v1/invitations')->assertOk();

    // Même forme que /api/v1/personnel : liste plate sous `data`, pagination sous `meta`.
    expect($response->json('data'))->toBeArray();
    expect($response->json('meta.total'))->toBe(1);
    expect($response->json('meta.current_page'))->toBe(1);
});

it('can fetch a single invitation instead of 500ing', function () {
    [, $school, $admin] = inviteOwner();

    $invitation = Invitation::create([
        'school_id' => $school->id,
        'email' => 'a@example.test',
        'firstname' => 'A',
        'lastname' => 'B',
        'role' => 'TEACHER',
        'token' => hash('sha256', 'x'),
        'status' => 'PENDING',
        'expires_at' => now()->addDays(7),
        'created_by' => $admin->id,
    ]);

    test()->withToken($admin->test_token)
        ->getJson("/api/v1/invitations/{$invitation->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $invitation->id)
        ->assertJsonMissingPath('data.token');
});

it('refuses a cross-tenant invitation with a non-empty message', function () {
    [, , $admin] = inviteOwner();
    $otherSchool = ownerWithActiveSubscription()->test_school;

    $foreign = Invitation::create([
        'school_id' => $otherSchool->id,
        'email' => 'a@example.test',
        'firstname' => 'A',
        'lastname' => 'B',
        'role' => 'TEACHER',
        'token' => hash('sha256', 'y'),
        'status' => 'PENDING',
        'expires_at' => now()->addDays(7),
        'created_by' => $admin->id,
    ]);

    $response = test()->withToken($admin->test_token)
        ->getJson("/api/v1/invitations/{$foreign->id}")
        ->assertStatus(403);

    expect($response->json('message'))->not->toBe('');
});
