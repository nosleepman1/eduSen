<?php

namespace App\Repositories\Api\V1\School;

use App\Enums\PersonnelStatusEnum;
use App\Models\Teacher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TeacherRepository
{
    public function countActive(string $schoolId): int
    {
        return Teacher::query()
            ->whereHas('personnel', fn ($query) => $query
                ->where('school_id', $schoolId)
                ->where('status', PersonnelStatusEnum::Active))
            ->count();
    }

    public function paginateForSchool(string $schoolId, int $perPage = 15): LengthAwarePaginator
    {
        return Teacher::query()
            ->whereHas('personnel', fn ($query) => $query->where('school_id', $schoolId))
            ->with('personnel.user')
            ->paginate($perPage);
    }
}
