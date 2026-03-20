<?php

use App\Enums\RoleEnum;
use App\Models\Personnel;

it('exposes the active roles of a personnel record', function () {
    $owner = ownerWithActiveSubscription();
    $secretary = staffMember($owner->test_school, RoleEnum::Secretary);

    $personnelId = Personnel::where('user_id', $secretary->id)->value('id');

    test()->withToken($owner->test_token)
        ->getJson("/api/v1/personnel/{$personnelId}")
        ->assertOk()
        ->assertJsonPath('data.roles', ['SECRETARY']);
});

it('reflects a role change made through updateRoles', function () {
    $owner = ownerWithActiveSubscription();
    $teacher = staffMember($owner->test_school, RoleEnum::Teacher);
    $personnelId = Personnel::where('user_id', $teacher->id)->value('id');

    test()->withToken($owner->test_token)
        ->patchJson("/api/v1/personnel/{$personnelId}/roles", ['roles' => ['SUPERVISOR', 'LIBRARIAN']])
        ->assertOk();

    $roles = test()->withToken($owner->test_token)
        ->getJson("/api/v1/personnel/{$personnelId}")
        ->assertOk()
        ->json('data.roles');

    expect($roles)->toEqualCanonicalizing(['SUPERVISOR', 'LIBRARIAN']);
});
