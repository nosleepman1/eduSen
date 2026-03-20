<?php

namespace App\Services\Api\V1\Auth;

use App\Enums\RoleEnum;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Validation\ValidationException;

class RoleAssignmentService
{
    /**
     * Assign a role to a user in a specific school.
     */
    public function assignRole(User $user, string $schoolId, RoleEnum $role, ?User $assigner = null): UserRole
    {
        // Check if role already exists
        $existing = UserRole::where('user_id', $user->id)
            ->where('school_id', $schoolId)
            ->where('role', $role)
            ->first();

        if ($existing) {
            // Reactivate if it was deactivated
            if (! $existing->is_active) {
                $existing->update(['is_active' => true, 'assigned_at' => now()]);
            }

            return $existing;
        }

        return UserRole::create([
            'user_id' => $user->id,
            'school_id' => $schoolId,
            'role' => $role,
            'assigned_by' => $assigner?->id,
            'assigned_at' => now(),
            'is_active' => true,
        ]);
    }

    /**
     * Revoke a specific role assignment (soft — sets is_active = false).
     */
    public function revokeRole(UserRole $userRole): void
    {
        // Cannot revoke OWNER role
        if ($userRole->role === RoleEnum::Owner) {
            throw ValidationException::withMessages([
                'role' => 'Le rôle propriétaire ne peut pas être révoqué.',
            ]);
        }

        $userRole->update(['is_active' => false]);
    }

    /**
     * Sync roles for a user in a specific school.
     * Activates the given roles, deactivates all others.
     */
    public function syncRolesForSchool(User $user, string $schoolId, array $roles, ?User $assigner = null): void
    {
        // Deactivate all existing roles for this school
        UserRole::where('user_id', $user->id)
            ->where('school_id', $schoolId)
            ->where('role', '!=', RoleEnum::Owner) // Never touch Owner
            ->update(['is_active' => false]);

        // Assign/reactivate each requested role
        foreach ($roles as $role) {
            $roleEnum = $role instanceof RoleEnum ? $role : RoleEnum::from($role);
            $this->assignRole($user, $schoolId, $roleEnum, $assigner);
        }
    }

    /**
     * Assign SuperAdmin role (platform level, no school).
     */
    public function assignSuperAdmin(User $user, ?User $assigner = null): UserRole
    {
        $existing = UserRole::where('user_id', $user->id)
            ->whereNull('school_id')
            ->where('role', RoleEnum::SuperAdmin)
            ->first();

        if ($existing) {
            if (! $existing->is_active) {
                $existing->update(['is_active' => true]);
            }

            return $existing;
        }

        return UserRole::create([
            'user_id' => $user->id,
            'school_id' => null,
            'role' => RoleEnum::SuperAdmin,
            'assigned_by' => $assigner?->id,
            'assigned_at' => now(),
            'is_active' => true,
        ]);
    }
}
