<?php

namespace App\Services\Api\V1\Auth;

use App\Enums\PersonnelStatusEnum;
use App\Enums\UserStatusEnum;
use App\Models\Personnel;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    public function __construct(
        private readonly PermissionService $permissions
    ) {}

    public function setPassword(array $data): void
    {
        $status = Password::broker()->reset($data, function (User $user, string $password): void {
            $user->forceFill([
                'password' => Hash::make($password),
                'password_changed_at' => now(),
            ])->save();
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['token' => trans($status)]);
        }
    }

    /**
     * Login step 1: Authenticate and return available schools.
     */
    public function login(array $credentials): array
    {
        if (! Auth::attempt($credentials)) {
            abort(401, 'Identifiants invalides.');
        }

        /** @var User $user */
        $user = Auth::user();

        if ($user->status !== UserStatusEnum::Active) {
            abort(403, 'Votre compte est '.$user->status->value.'.');
        }

        $user->forceFill(['last_login_at' => now()])->save();

        // Check if user is a SuperAdmin (platform-level)
        if ($user->isSuperAdmin()) {
            $token = $user->createToken('api', ['role' => 'SUPER_ADMIN'])->plainTextToken;

            return [
                'token' => $token,
                'user' => $user,
                'type' => 'super_admin',
                'mustChangePassword' => $user->mustChangePassword(),
                'permissions' => $this->permissions->effectiveFor($user, null),
            ];
        }

        // Fetch all active personnel records (schools the user belongs to)
        $personnelRecords = Personnel::where('user_id', $user->id)
            ->where('status', PersonnelStatusEnum::Active)
            ->with(['school:id,name,code,is_school_setup'])
            ->get();

        if ($personnelRecords->isEmpty()) {
            abort(403, 'Aucun établissement associé à ce compte.');
        }

        // Single school — auto-select, return full token
        if ($personnelRecords->count() === 1) {
            $personnel = $personnelRecords->first();

            $role = UserRole::where('user_id', $user->id)
                ->where('school_id', $personnel->school_id)
                ->where('is_active', true)
                ->first();

            $token = $user->createToken('api', [
                'school_id' => $personnel->school_id,
            ])->plainTextToken;

            return [
                'token' => $token,
                'user' => $user,
                'type' => 'single_school',
                'school' => $personnel->school,
                'role' => $role?->role,
                'mustChangePassword' => $user->mustChangePassword(),
                'schoolSetupRequired' => ! $personnel->school->isSetupComplete(),
                'permissions' => $this->permissions->effectiveFor($user, $personnel->school_id),
            ];
        }

        // Multiple schools — return temp token + school list for selection
        $tempToken = $user->createToken('school-select', ['scope' => 'select-school'])->plainTextToken;

        $schools = $personnelRecords->map(function (Personnel $p) use ($user) {
            $role = UserRole::where('user_id', $user->id)
                ->where('school_id', $p->school_id)
                ->where('is_active', true)
                ->first();

            return [
                'id' => $p->school_id,
                'name' => $p->school->name,
                'code' => $p->school->code,
                'role' => $role?->role,
            ];
        });

        return [
            'token' => $tempToken,
            'user' => $user,
            'type' => 'multi_school',
            'schools' => $schools,
            'mustChangePassword' => $user->mustChangePassword(),
        ];
    }

    /**
     * Login step 2: Select a school and get a scoped token.
     */
    public function selectSchool(User $user, string $schoolId): array
    {
        $personnel = Personnel::where('user_id', $user->id)
            ->where('school_id', $schoolId)
            ->where('status', PersonnelStatusEnum::Active)
            ->with('school')
            ->first();

        if (! $personnel) {
            abort(403, 'Vous n\'avez pas accès à cet établissement.');
        }

        // Delete the temporary school-select token
        $this->revokeCurrentToken($user);

        // Create the real scoped token
        $token = $user->createToken('api', [
            'school_id' => $personnel->school_id,
        ])->plainTextToken;

        $role = UserRole::where('user_id', $user->id)
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->first();

        return [
            'token' => $token,
            'user' => $user,
            'school' => $personnel->school,
            'role' => $role?->role,
            'schoolSetupRequired' => ! $personnel->school->isSetupComplete(),
            'permissions' => $this->permissions->effectiveFor($user, $schoolId),
        ];
    }

    /**
     * Logout — revoke current token.
     */
    public function logout(User $user): void
    {
        $this->revokeCurrentToken($user);
    }

    /**
     * Sanctum hands back a TransientToken when the request was authenticated by
     * session rather than by a bearer token, and TransientToken has no delete().
     * Both clients send `withCredentials: true`, so a session cookie alongside
     * the bearer token is a reachable state — calling delete() blindly turns
     * logout and select-school into a 500.
     */
    private function revokeCurrentToken(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }

    /**
     * Get current user info with school context.
     */
    public function me(User $user): array
    {
        $data = ['user' => $user];

        // Same TransientToken caveat as revokeCurrentToken(): a session-authenticated
        // request carries no abilities, hence no school context.
        $token = $user->currentAccessToken();
        $schoolId = $token instanceof PersonalAccessToken
            ? ($token->abilities['school_id'] ?? null)
            : null;

        if ($schoolId) {
            $personnel = $user->personnelForSchool($schoolId);
            $role = UserRole::where('user_id', $user->id)
                ->where('school_id', $schoolId)
                ->where('is_active', true)
                ->first();

            $data['school'] = $personnel?->school;
            $data['role'] = $role?->role;
            $data['personnel'] = $personnel;
        }

        // Always present, so a client can refresh permissions without having to
        // branch on whether a school context exists.
        $data['permissions'] = $this->permissions->effectiveFor($user, $schoolId);

        return $data;
    }
}
