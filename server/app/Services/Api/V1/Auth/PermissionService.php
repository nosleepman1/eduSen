<?php

namespace App\Services\Api\V1\Auth;

use App\Enums\RoleEnum;
use App\Models\RolePermission;
use App\Models\User;
use App\Models\UserRole;

/**
 * Resolves the permissions a user effectively holds inside a school.
 *
 * Single source of truth for two consumers that must never disagree:
 * CheckPermission, which enforces access, and AuthService, which tells the
 * client what to render. If they drifted, the UI would offer actions the API
 * refuses — or hide actions the user is entitled to.
 */
class PermissionService
{
    /**
     * Stands for "every permission". SuperAdmin and Owner bypass permission
     * checks entirely rather than being granted an enumerated set, so there is
     * no list to return for them — this sentinel reports that faithfully.
     */
    public const WILDCARD = '*';

    /**
     * @return list<string>
     */
    public function effectiveFor(User $user, ?string $schoolId): array
    {
        if ($user->isSuperAdmin()) {
            return [self::WILDCARD];
        }

        if ($schoolId === null) {
            return [];
        }

        $roles = $this->activeRolesIn($user, $schoolId);

        if ($roles === []) {
            return [];
        }

        if (in_array(RoleEnum::Owner->value, $roles, true)) {
            return [self::WILDCARD];
        }

        return RolePermission::getCachedPermissions($schoolId, $roles);
    }

    /**
     * @param  list<string>  $granted
     */
    public function allows(array $granted, string $permission): bool
    {
        return in_array(self::WILDCARD, $granted, true)
            || in_array($permission, $granted, true);
    }

    /**
     * @return list<string>
     */
    public function activeRolesIn(User $user, string $schoolId): array
    {
        return UserRole::query()
            ->where('user_id', $user->id)
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->pluck('role')
            ->map(fn ($role) => $role instanceof RoleEnum ? $role->value : (string) $role)
            ->values()
            ->all();
    }
}
