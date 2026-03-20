<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Creates an OWNER user with an active subscription for a fresh school.
 * Sets $user->test_token (Sanctum bearer token) and $user->test_school.
 */
function ownerWithActiveSubscription(): \App\Models\User
{
    $owner = \App\Models\User::factory()->create(['password_changed_at' => now()]);
    $school = \App\Models\School::factory()->create(['owner_id' => $owner->id, 'is_school_setup' => true]);

    \App\Models\Personnel::create([
        'user_id' => $owner->id,
        'school_id' => $school->id,
        'status' => \App\Enums\PersonnelStatusEnum::Active,
        'registration_number' => 'EMP-' . uniqid(),
    ]);

    \App\Models\UserRole::create([
        'user_id' => $owner->id,
        'school_id' => $school->id,
        'role' => \App\Enums\RoleEnum::Owner,
        'is_active' => true,
        'assigned_at' => now(),
    ]);

    $plan = \App\Models\SubscriptionPlan::create([
        'name' => 'Starter',
        'price' => 10000,
        'type' => 'MONTHLY',
        'duration' => 1,
        'max_students' => 200,
        'max_teachers' => 20,
        'features' => [],
    ]);

    \App\Models\Subscription::create([
        'school_id' => $school->id,
        'subscription_plan_id' => $plan->id,
        'status' => 'ACTIVE',
        'starts_at' => now(),
        'ends_at' => now()->addYear(),
    ]);

    $owner->test_token = $owner->createToken('api', ['school_id' => $school->id])->plainTextToken;
    $owner->test_school = $school;

    return $owner;
}

/**
 * Creates a staff member with the given role inside an existing school,
 * with the school's default RolePermission set seeded so permission-gated
 * routes behave as they would in production. Sets $user->test_token.
 */
function staffMember(\App\Models\School $school, \App\Enums\RoleEnum $role): \App\Models\User
{
    \Database\Seeders\DefaultPermissionsSeeder::seedPermissionsForSchool($school->id);

    $staff = \App\Models\User::factory()->create(['password_changed_at' => now()]);

    \App\Models\Personnel::create([
        'user_id' => $staff->id,
        'school_id' => $school->id,
        'status' => \App\Enums\PersonnelStatusEnum::Active,
        'registration_number' => 'EMP-' . uniqid(),
    ]);

    \App\Models\UserRole::create([
        'user_id' => $staff->id,
        'school_id' => $school->id,
        'role' => $role,
        'is_active' => true,
        'assigned_at' => now(),
    ]);

    $staff->test_token = $staff->createToken('api', ['school_id' => $school->id])->plainTextToken;

    return $staff;
}
