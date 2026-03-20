<?php

use App\Enums\PersonnelStatusEnum;
use App\Enums\RoleEnum;
use App\Models\Personnel;
use App\Models\RolePermission;
use App\Models\School;
use App\Models\User;
use App\Models\UserRole;
use App\Services\Api\V1\Auth\PermissionService;
use Database\Seeders\DefaultPermissionsSeeder;

function memberOf(School $school, RoleEnum $role, string $password = 'password'): User
{
    $user = User::factory()->create([
        'password' => bcrypt($password),
        'password_changed_at' => now(),
    ]);

    Personnel::create([
        'user_id' => $user->id,
        'school_id' => $school->id,
        'status' => PersonnelStatusEnum::Active,
        'registration_number' => 'EMP-'.uniqid(),
    ]);

    UserRole::create([
        'user_id' => $user->id,
        'school_id' => $school->id,
        'role' => $role,
        'is_active' => true,
        'assigned_at' => now(),
    ]);

    return $user;
}

function login(User $user, string $password = 'password')
{
    return test()->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => $password,
    ]);
}

// ── Contenu de la réponse de login ───────────────────────────────

it('returns the granted permissions of a single-school member', function () {
    $school = School::factory()->create(['is_school_setup' => true]);
    DefaultPermissionsSeeder::seedPermissionsForSchool($school->id);
    $secretary = memberOf($school, RoleEnum::Secretary);

    $response = login($secretary)->assertOk();

    $permissions = $response->json('data.permissions');

    expect($permissions)->toBeArray()
        ->and($permissions)->toContain('student.create')
        ->and($permissions)->toContain('level.view')
        // Une secrétaire ne gère pas la structure académique.
        ->and($permissions)->not->toContain('level.manage')
        ->and($permissions)->not->toContain('personnel.create');
});

it('reports the wildcard for an owner rather than an enumerated list', function () {
    $school = School::factory()->create(['is_school_setup' => true]);
    DefaultPermissionsSeeder::seedPermissionsForSchool($school->id);
    $owner = memberOf($school, RoleEnum::Owner);

    login($owner)->assertOk()->assertJsonPath('data.permissions', ['*']);
});

it('reports the wildcard for a super admin', function () {
    $admin = User::factory()->create([
        'password' => bcrypt('password'),
        'password_changed_at' => now(),
    ]);

    UserRole::create([
        'user_id' => $admin->id,
        'school_id' => null,
        'role' => RoleEnum::SuperAdmin,
        'is_active' => true,
        'assigned_at' => now(),
    ]);

    login($admin)
        ->assertOk()
        ->assertJsonPath('data.type', 'super_admin')
        ->assertJsonPath('data.permissions', ['*']);
});

it('exposes permissions on /auth/me', function () {
    $school = School::factory()->create(['is_school_setup' => true]);
    DefaultPermissionsSeeder::seedPermissionsForSchool($school->id);
    $teacher = memberOf($school, RoleEnum::Teacher);
    $token = $teacher->createToken('api', ['school_id' => $school->id])->plainTextToken;

    $permissions = test()->withToken($token)
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->json('data.permissions');

    expect($permissions)->toContain('grade.manage')
        ->and($permissions)->not->toContain('student.create');
});

it('exposes permissions after selecting a school', function () {
    $first = School::factory()->create(['is_school_setup' => true]);
    $second = School::factory()->create(['is_school_setup' => true]);
    DefaultPermissionsSeeder::seedPermissionsForSchool($first->id);
    DefaultPermissionsSeeder::seedPermissionsForSchool($second->id);

    $user = memberOf($first, RoleEnum::Accountant);
    Personnel::create([
        'user_id' => $user->id,
        'school_id' => $second->id,
        'status' => PersonnelStatusEnum::Active,
        'registration_number' => 'EMP-'.uniqid(),
    ]);
    UserRole::create([
        'user_id' => $user->id,
        'school_id' => $second->id,
        'role' => RoleEnum::Librarian,
        'is_active' => true,
        'assigned_at' => now(),
    ]);

    $tempToken = login($user)->assertOk()->assertJsonPath('data.type', 'multi_school')->json('data.token');

    // Le rôle diffère d'une école à l'autre : les permissions doivent suivre
    // l'école choisie, pas l'utilisateur.
    $permissions = test()->withToken($tempToken)
        ->postJson('/api/v1/auth/select-school', ['school_id' => $second->id])
        ->assertOk()
        ->json('data.permissions');

    expect($permissions)->toContain('library.create')
        ->and($permissions)->not->toContain('payment.refund');
});

// ── Cohérence avec ce que le middleware applique ──────────────────

it('advertises exactly what the middleware enforces', function () {
    // ownerWithActiveSubscription() fournit l'abonnement actif exigé par le
    // middleware subscription.active sur les routes académiques.
    $school = ownerWithActiveSubscription()->test_school;
    $teacher = staffMember($school, RoleEnum::Teacher);

    $permissions = app(PermissionService::class)->effectiveFor($teacher, $school->id);

    // Annoncé accordé => la route passe.
    expect($permissions)->toContain('level.view');
    test()->withToken($teacher->test_token)
        ->getJson('/api/v1/academic/levels')
        ->assertOk();

    // Annoncé refusé => la route refuse.
    expect($permissions)->not->toContain('level.manage');
    test()->withToken($teacher->test_token)
        ->postJson('/api/v1/academic/levels', [])
        ->assertStatus(403)
        ->assertJsonPath('code', 'PERMISSION_DENIED');
});

it('grants nothing to a member with no active role', function () {
    $school = School::factory()->create(['is_school_setup' => true]);
    DefaultPermissionsSeeder::seedPermissionsForSchool($school->id);
    $user = memberOf($school, RoleEnum::Teacher);
    UserRole::where('user_id', $user->id)->update(['is_active' => false]);

    expect(login($user)->json('data.permissions'))->toBe([]);
});

// ── Invalidation du cache ────────────────────────────────────────

it('reflects a permission change without waiting for the cache to expire', function () {
    $school = School::factory()->create(['is_school_setup' => true]);
    DefaultPermissionsSeeder::seedPermissionsForSchool($school->id);
    $secretary = memberOf($school, RoleEnum::Secretary);

    expect(login($secretary)->json('data.permissions'))->not->toContain('level.manage');

    // Une école élargit les droits de ses secrétaires.
    $row = RolePermission::where('school_id', $school->id)
        ->where('role', RoleEnum::Secretary)
        ->firstOrFail();
    $row->update(['permissions' => [...$row->permissions, 'level.manage']]);

    expect(login($secretary)->json('data.permissions'))->toContain('level.manage');
});

it('clears the cache on any driver, without Redis', function () {
    // Le driver de test est `array`. L'ancienne invalidation passait par
    // Redis::connection() et n'aurait rien vidé ici — voire aurait levé.
    expect(config('cache.default'))->not->toBe('redis');

    $school = School::factory()->create();
    DefaultPermissionsSeeder::seedPermissionsForSchool($school->id);

    $before = RolePermission::getCachedPermissions($school->id, [RoleEnum::Librarian->value]);
    expect($before)->toContain('library.view');

    RolePermission::where('school_id', $school->id)
        ->where('role', RoleEnum::Librarian)
        ->firstOrFail()
        ->update(['permissions' => ['library.view', 'library.archive']]);

    expect(RolePermission::getCachedPermissions($school->id, [RoleEnum::Librarian->value]))
        ->toContain('library.archive');
});

it('combines the permissions of several roles held in one school', function () {
    $school = School::factory()->create(['is_school_setup' => true]);
    DefaultPermissionsSeeder::seedPermissionsForSchool($school->id);
    $user = memberOf($school, RoleEnum::Librarian);

    UserRole::create([
        'user_id' => $user->id,
        'school_id' => $school->id,
        'role' => RoleEnum::Accountant,
        'is_active' => true,
        'assigned_at' => now(),
    ]);

    $permissions = app(PermissionService::class)->effectiveFor($user->fresh(), $school->id);

    expect($permissions)->toContain('library.create')
        ->and($permissions)->toContain('payment.refund');
});
