<?php

namespace App\Services\Api\V1\School;

use App\Enums\PersonnelStatusEnum;
use App\Models\Personnel;
use App\Models\UserRole;
use App\Services\Api\V1\Auth\RoleAssignmentService;
use Illuminate\Support\Facades\DB;

class PersonnelService
{
    public function __construct(
        private RoleAssignmentService $roleAssignment
    ) {}

    public function update(Personnel $personnel, array $data): Personnel
    {
        $personnel->update(array_intersect_key($data, array_flip([
            'department', 'position', 'hire_date',
        ])));

        return $personnel->fresh();
    }

    public function archive(Personnel $personnel): void
    {
        DB::transaction(function () use ($personnel) {
            UserRole::where('user_id', $personnel->user_id)
                ->where('school_id', $personnel->school_id)
                ->update(['is_active' => false]);

            $personnel->update(['status' => PersonnelStatusEnum::Suspended]);
            $personnel->delete();
        });
    }

    public function restore(Personnel $personnel): Personnel
    {
        return DB::transaction(function () use ($personnel) {
            $personnel->restore();
            $personnel->update(['status' => PersonnelStatusEnum::Active]);

            // Mirrors archive(): that deactivated every role for this user+school,
            // so restoring reinstates exactly those.
            UserRole::where('user_id', $personnel->user_id)
                ->where('school_id', $personnel->school_id)
                ->update(['is_active' => true]);

            return $personnel->fresh();
        });
    }

    public function updateRoles(Personnel $personnel, array $roles, ?\App\Models\User $assigner = null): void
    {
        $this->roleAssignment->syncRolesForSchool($personnel->user, $personnel->school_id, $roles, $assigner);
    }
}
